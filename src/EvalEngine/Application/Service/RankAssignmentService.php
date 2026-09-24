<?php
namespace Src\EvalEngine\Application\Service;

use Src\EvalEngine\Domain\Evaluation\EvaluationResultRepository;
use Src\LearnerProfile\Application\Command\AssignInitialRank\AssignInitialRankCommand;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Command\CompleteDiagnosticSession\CompleteDiagnosticSessionCommand;
use Src\SimExecution\Application\Query\FindDiagnosticSessionForTask\FindDiagnosticSessionForTaskQuery;
use Src\SimExecution\Application\Query\GetLearnerSession\GetLearnerSessionQuery;
use Src\Submission\Application\Query\GetLatestSubmissionId\GetLatestSubmissionIdQuery;
use Src\Simulation\Application\Query\ListTasksByScenario\ListTasksByScenarioQuery;
use Src\Simulation\Domain\Task\Task;

/**
 * Implementation Plan §5.5: "After the diagnostic: DiagnosticSession completed
 * -> RankAssignmentService reads evaluation results -> writes initial rank to
 * LearnerProfile -> creates RankEvent." No formula for turning diagnostic
 * performance into a rank exists in any project doc (checked BLD §4.3,
 * Integration Spec §19/§20 directly) — the mapping below is a documented
 * default, same judgment-call pattern as LearnerProfile::FAILURE_STREAK_THRESHOLD.
 */
final class RankAssignmentService
{
    private const TIER_SCORES = [
        'beginning'     => 0,
        'developing'    => 1,
        'proficient'    => 2,
        'distinguished' => 3,
    ];

    /**
     * The diagnostic is written for first-year developers, so a solidly
     * proficient average (2.0) lands at the top of Junior; Mid needs mostly
     * distinguished work and Senior needs near-perfect answers. Rank escalation
     * moves learners up quickly once real project evidence arrives.
     * [minScore, maxScore, RankTier value].
     */
    private const TIER_BOUNDARIES = [
        [0.0, 2.25, 'Junior'],
        [2.25, 2.85, 'Mid'],
        [2.85, 3.0001, 'Senior'],
    ];

    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly CommandBus $commandBus,
        private readonly EvaluationResultRepository $evaluationResults,
    ) {}

    public function maybeAssignFromDiagnostic(string $learnerId, string $learnerSessionId, string $diagnosticSessionId): void
    {
        if (! $this->allDiagnosticTasksAttempted($learnerSessionId)) {
            return;
        }

        $tiers = $this->evaluationResults->findAllDimensionTiersForSession($learnerSessionId);
        if ($tiers === []) {
            return;
        }

        [$rankTier, $rankLevel] = $this->mapTiersToRank($tiers);

        $this->assign($learnerId, $learnerSessionId, $diagnosticSessionId, $rankTier, $rankLevel,
            'Diagnostic scenario completed — rank computed from aggregated diagnostic submission quality.');
    }

    /**
     * Used when a diagnostic submission could not be evaluated (AI evaluation
     * disabled or the provider failed). Without it the learner would be held on
     * /learn/diagnostic forever by EnsureDiagnosticComplete. Falls back to the
     * entry rank; rank escalation corrects it once real evaluations flow.
     */
    public function assignWithoutEvaluation(string $learnerId, string $learnerSessionId, string $taskId): void
    {
        $diagnosticSessionId = $this->queryBus->ask(new FindDiagnosticSessionForTaskQuery($learnerSessionId, $taskId));
        if ($diagnosticSessionId === null || ! $this->allDiagnosticTasksAttempted($learnerSessionId)) {
            return;
        }

        $tiers = $this->evaluationResults->findAllDimensionTiersForSession($learnerSessionId);
        [$rankTier, $rankLevel] = $tiers !== [] ? $this->mapTiersToRank($tiers) : ['Junior', 1];

        $this->assign($learnerId, $learnerSessionId, $diagnosticSessionId, $rankTier, $rankLevel,
            'Diagnostic scenario completed without AI evaluation — entry rank assigned.');
    }

    private function assign(string $learnerId, string $learnerSessionId, string $diagnosticSessionId, string $rankTier, int $rankLevel, string $reason): void
    {
        $this->commandBus->dispatch(new AssignInitialRankCommand(
            learnerId:        $learnerId,
            rankTier:         $rankTier,
            rankLevel:        $rankLevel,
            triggerReason:    $reason,
            sourceSessionId:  $learnerSessionId,
        ));

        $this->commandBus->dispatch(new CompleteDiagnosticSessionCommand(
            learnerSessionId:      $learnerSessionId,
            diagnosticSessionId:   $diagnosticSessionId,
            assignedRankTier:      $rankTier,
            assignedRankLevel:     $rankLevel,
        ));
    }

    private function allDiagnosticTasksAttempted(string $learnerSessionId): bool
    {
        $session = $this->queryBus->ask(new GetLearnerSessionQuery($learnerSessionId));
        if ($session === null || $session->currentScenarioId === null) {
            return false;
        }

        /** @var Task[] $tasks */
        $tasks = $this->queryBus->ask(new ListTasksByScenarioQuery($session->currentScenarioId));
        $liveTaskIds = [];
        foreach ($tasks as $task) {
            $p = $task->toPrimitives();
            if ($p['is_published'] && $p['is_active']) {
                $liveTaskIds[] = $task->id();
            }
        }

        if ($liveTaskIds === []) {
            return false;
        }

        foreach ($liveTaskIds as $taskId) {
            if ($this->queryBus->ask(new GetLatestSubmissionIdQuery($learnerSessionId, $taskId)) === null) {
                return false;
            }
        }

        return true;
    }

    /** @param string[] $tiers @return array{0: string, 1: int} */
    private function mapTiersToRank(array $tiers): array
    {
        $scores = array_map(fn (string $tier) => self::TIER_SCORES[$tier] ?? 0, $tiers);
        $average = array_sum($scores) / count($scores);

        foreach (self::TIER_BOUNDARIES as [$min, $max, $tierValue]) {
            if ($average >= $min && $average < $max) {
                $span = ($max - $min) / 3;
                $level = (int) floor(($average - $min) / $span) + 1;
                return [$tierValue, max(1, min(3, $level))];
            }
        }

        return ['Senior', 3];
    }
}
