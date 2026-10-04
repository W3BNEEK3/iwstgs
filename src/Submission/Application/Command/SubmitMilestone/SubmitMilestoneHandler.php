<?php
namespace Src\Submission\Application\Command\SubmitMilestone;

use Src\LearnerProfile\Application\Query\GetLearnerRank\GetLearnerRankQuery;
use Src\Shared\Application\Bus\QueryBus;
use Src\Shared\Infrastructure\Feature\FeatureFlagService;
use Src\SimExecution\Application\Query\GetBuildProgress\BuildProgressView;
use Src\SimExecution\Application\Query\GetBuildProgress\GetBuildProgressQuery;
use Src\SimExecution\Application\Query\GetLearnerIdForUser\GetLearnerIdForUserQuery;
use Src\SimExecution\Application\Query\GetLearnerSession\GetLearnerSessionQuery;
use Src\SimExecution\Application\Query\IsTaskInjectedForSession\IsTaskInjectedForSessionQuery;
use Src\Simulation\Application\Query\GetTask\GetTaskQuery;
use Src\SourceControl\Application\Query\GetCommitDiff\GetCommitDiffQuery;
use Src\SourceControl\Application\Query\GetLinkedRepository\GetLinkedRepositoryQuery;
use Src\SourceControl\Domain\HostUnavailable;
use Src\Submission\Application\Service\CiFinaliser;
use Src\Submission\Domain\Exceptions\LearnerNotFoundException;
use Src\Submission\Domain\Exceptions\SubmissionNotAllowedException;
use Src\Submission\Domain\Submission\SubmissionPackageRepository;

/**
 * Submits a commit from the learner's own repository as a milestone (design
 * doc v2-01 §2.2). The changes since the last accepted milestone become the
 * code layer, the learner's explanation the written layer; then the
 * submission waits for the commit's acceptance-test run (CiFinaliser) before
 * it is evaluated. Milestones unlock one at a time; fixes the platform
 * injected (consequence and suggestion cards) can be submitted any time.
 */
final class SubmitMilestoneHandler
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly SubmissionPackageRepository $packages,
        private readonly CiFinaliser $finaliser,
        private readonly FeatureFlagService $flags,
    ) {}

    public function handle(SubmitMilestoneCommand $command): void
    {
        if (! $this->flags->isEnabled('sourcecontrol.github')) {
            throw new SubmissionNotAllowedException('submitting from GitHub is not switched on');
        }
        $learnerId = $command->userId !== null ? $this->queryBus->ask(new GetLearnerIdForUserQuery($command->userId)) : null;
        if ($learnerId === null) {
            throw new LearnerNotFoundException();
        }

        $session = $this->queryBus->ask(new GetLearnerSessionQuery($command->sessionId));
        if ($session === null || $session->learnerId !== $learnerId) {
            throw new SubmissionNotAllowedException('this session does not belong to you');
        }
        if ($session->status !== 'active' || $session->stackVariantId === null) {
            throw new SubmissionNotAllowedException('this project is not being built in a repository');
        }

        $task = $this->queryBus->ask(new GetTaskQuery($command->taskId));
        if ($task === null || ! $task->isPublished()) {
            throw new SubmissionNotAllowedException('this task is not available');
        }

        /** @var BuildProgressView $progress */
        $progress = $this->queryBus->ask(new GetBuildProgressQuery($session->id));
        $isInjected = (bool) $this->queryBus->ask(new IsTaskInjectedForSessionQuery($session->id, $command->taskId));
        if (! $progress->isCurrent($command->taskId) && ! $isInjected) {
            throw new SubmissionNotAllowedException($progress->isMilestone($command->taskId)
                ? 'this milestone isn\'t open yet: finish the current one first'
                : 'this task is not part of your build');
        }

        if (trim($command->explanation) === '') {
            throw new SubmissionNotAllowedException('please explain what you did and why');
        }
        if (! preg_match('/^[0-9a-f]{7,40}$/i', $command->commitSha)) {
            throw new SubmissionNotAllowedException('please choose a commit');
        }

        $repo = $this->queryBus->ask(new GetLinkedRepositoryQuery($session->id));
        if ($repo === null || ! $repo->isUsable()) {
            throw new SubmissionNotAllowedException('link your GitHub repository first');
        }

        try {
            $diff = $this->queryBus->ask(new GetCommitDiffQuery($session->id, $repo->diffBase(), $command->commitSha));
        } catch (HostUnavailable) {
            throw new SubmissionNotAllowedException('we couldn\'t read that commit from GitHub. Check it\'s pushed and try again');
        }

        $rank = $this->queryBus->ask(new GetLearnerRankQuery($learnerId));
        $taskPrimitives = $task->toPrimitives();

        $submissionId = $this->packages->create(
            learnerSessionId:       $session->id,
            learnerId:              $learnerId,
            taskId:                 $command->taskId,
            scenarioId:             $taskPrimitives['scenario_id'],
            sprintId:               null, // Build sessions have no sprints
            attemptNumber:          $this->packages->countAttempts($session->id, $command->taskId) + 1,
            layer1Text:             trim($command->explanation),
            layer2ArtifactIds:      null,
            layer3Code:             $diff?->text,
            layer3ExecutionResult:  null,
            layer4PlanningSnapshot: null,
            cacComplexityAtSub:     $rank?->cacComplexity ?? 'low',
            cacAutonomyAtSub:       $rank?->cacAutonomy ?? 'mid',
            cacContextAtSub:        $rank?->cacContextFidelity ?? 'low',
            rankAtSubmission:       ($rank?->rankTier ?? 'Junior') . '-' . ($rank?->rankLevel ?? 1),
        );
        $this->packages->attachCommit($submissionId, $command->commitSha, $repo->diffBase(), $diff?->summary ?? []);

        // If the tests already finished for this commit, evaluation starts now; otherwise it waits.
        $this->finaliser->finalise($submissionId);
    }
}
