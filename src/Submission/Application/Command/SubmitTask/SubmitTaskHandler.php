<?php
namespace Src\Submission\Application\Command\SubmitTask;

use Illuminate\Support\Str;
use Src\LearnerProfile\Application\Query\GetLearnerRank\GetLearnerRankQuery;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Query\GetLearnerIdForUser\GetLearnerIdForUserQuery;
use Src\SimExecution\Application\Query\GetLearnerSession\GetLearnerSessionQuery;
use Src\Simulation\Application\Query\GetTask\GetTaskQuery;
use Src\Simulation\Domain\Task\Task;
use Src\Submission\Application\Service\PlanningSnapshotAssembler;
use Src\Submission\Domain\Exceptions\LearnerNotFoundException;
use Src\Submission\Domain\Exceptions\MissingRequiredDeliverableException;
use Src\Submission\Domain\Exceptions\SubmissionNotAllowedException;
use Src\Submission\Domain\Submission\ArtifactType;
use Src\Submission\Domain\Submission\SubmissionArtifactRepository;
use Src\Submission\Domain\Submission\SubmissionPackageRepository;
use Src\Submission\Domain\Submission\SubmissionReceived;

/**
 * Validates and assembles a full four-layer submission package. CAC levels
 * and rank are snapshotted from the learner's current LearnerProfile — full
 * per-task CAC variant selection (Business Logic §7.3) is adaptive-engine
 * territory (Phase 9), not implemented yet; this snapshots "the CAC context
 * the learner was operating under", which is accurate even before that
 * exists. Exception: diagnostic-session submissions always snapshot mid/mid/mid
 * regardless of profile (BLD §4.2 — fixed, not runtime-adjusted), and are
 * allowed through with no active sprint (Integration Spec §10 — the Planning
 * Layer is inactive during diagnostic scenarios).
 */
final class SubmitTaskHandler
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly SubmissionPackageRepository $packages,
        private readonly SubmissionArtifactRepository $artifacts,
        private readonly PlanningSnapshotAssembler $planningSnapshotAssembler,
    ) {}

    public function handle(SubmitTaskCommand $command): void
    {
        if ($command->userId === null) {
            throw new LearnerNotFoundException();
        }

        $learnerId = $this->queryBus->ask(new GetLearnerIdForUserQuery($command->userId));
        if ($learnerId === null) {
            throw new LearnerNotFoundException();
        }

        $session = $this->queryBus->ask(new GetLearnerSessionQuery($command->sessionId));
        if ($session === null || $session->learnerId !== $learnerId) {
            throw new SubmissionNotAllowedException('this session does not belong to you');
        }

        $isDiagnostic = $session->status === 'diagnostic';

        if ($session->status !== 'active' && ! $isDiagnostic) {
            throw new SubmissionNotAllowedException('this session is not currently active');
        }

        // Integration Spec §10: the Planning Layer (sprint board) is inactive
        // during diagnostic scenarios — a null sprint is expected, not an error.
        if (! $isDiagnostic && $session->currentSprintId === null) {
            throw new SubmissionNotAllowedException('no sprint is currently active for this session');
        }

        /** @var Task|null $task */
        $task = $this->queryBus->ask(new GetTaskQuery($command->taskId));
        if ($task === null || ! $task->isPublished()) {
            throw new SubmissionNotAllowedException('this task is not available');
        }

        $taskPrimitives = $task->toPrimitives();
        if ($taskPrimitives['scenario_id'] !== $session->currentScenarioId) {
            throw new SubmissionNotAllowedException('this task is not part of your current scenario');
        }

        foreach ($task->expectedDeliverables() as $deliverable) {
            $primitives = $deliverable->toPrimitives();
            if (! $primitives['is_required']) {
                continue;
            }

            $satisfied = match ($primitives['type']) {
                'written_explanation' => $command->layer1Text !== null && trim($command->layer1Text) !== '',
                'code' => $command->layer3Code !== null && trim($command->layer3Code) !== '',
                'artifact', 'diagram', 'document' => count($command->artifacts) > 0,
                default => false,
            };

            if (! $satisfied) {
                throw new MissingRequiredDeliverableException($primitives['label']);
            }
        }

        $rank = $this->queryBus->ask(new GetLearnerRankQuery($learnerId));
        $rankTier = $rank?->rankTier ?? 'Junior';
        $rankLevel = $rank?->rankLevel ?? 1;

        // BLD §4.2: diagnostic tasks are fixed at mid Complexity/Autonomy/Context
        // Fidelity, never runtime-adjusted from the learner's (still-default) rank.
        if ($isDiagnostic) {
            $cacComplexityAtSub = 'mid';
            $cacAutonomyAtSub = 'mid';
            $cacContextAtSub = 'mid';
        } else {
            $cacComplexityAtSub = $rank?->cacComplexity ?? 'low';
            $cacAutonomyAtSub = $rank?->cacAutonomy ?? 'mid';
            $cacContextAtSub = $rank?->cacContextFidelity ?? 'low';
        }

        $attemptNumber = $this->packages->countAttempts($command->sessionId, $command->taskId) + 1;
        $planningSnapshot = $this->planningSnapshotAssembler->assemble($command->sessionId);

        $artifactIds = [];
        $storedArtifacts = [];
        foreach ($command->artifacts as $artifact) {
            $id = (string) Str::uuid();
            $artifactIds[] = $id;
            $storedArtifacts[] = [
                'id'          => $id,
                'filename'    => $artifact['filename'],
                'storagePath' => $artifact['storagePath'],
                'type'        => $artifact['type'],
            ];
        }

        $submissionId = $this->packages->create(
            learnerSessionId:       $command->sessionId,
            learnerId:              $learnerId,
            taskId:                 $command->taskId,
            scenarioId:             $taskPrimitives['scenario_id'],
            sprintId:               $session->currentSprintId,
            attemptNumber:          $attemptNumber,
            layer1Text:             $command->layer1Text,
            layer2ArtifactIds:      $artifactIds ?: null,
            layer3Code:             $command->layer3Code,
            layer3ExecutionResult:  null, // code execution is behind submission.code_execution, off for MVP
            layer4PlanningSnapshot: $planningSnapshot,
            cacComplexityAtSub:     $cacComplexityAtSub,
            cacAutonomyAtSub:       $cacAutonomyAtSub,
            cacContextAtSub:        $cacContextAtSub,
            rankAtSubmission:       "{$rankTier}-{$rankLevel}",
        );

        foreach ($storedArtifacts as $stored) {
            $this->artifacts->create(
                id:          $stored['id'],
                submissionId: $submissionId,
                filename:    $stored['filename'],
                type:        ArtifactType::from($stored['type']),
                storagePath: $stored['storagePath'],
            );
        }

        event(new SubmissionReceived(
            submissionId:      $submissionId,
            learnerId:         $learnerId,
            learnerSessionId:  $command->sessionId,
            taskId:            $command->taskId,
            attemptNumber:     $attemptNumber,
        ));
    }
}
