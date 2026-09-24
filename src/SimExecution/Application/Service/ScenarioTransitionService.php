<?php
namespace Src\SimExecution\Application\Service;

use Src\AIMediation\Application\Command\InjectQueuedSuggestions\InjectQueuedSuggestionsCommand;
use Src\AIMediation\Domain\Habit\HabitFlagRepository;
use Src\EvalEngine\Application\Query\GetSubmissionPassStatus\GetSubmissionPassStatusQuery;
use Src\LearnerProfile\Application\Command\EscalateCacComplexity\EscalateCacComplexityCommand;
use Src\LearnerProfile\Application\Command\GenerateFinalCompetencyGraph\GenerateFinalCompetencyGraphCommand;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Command\SetTargetedDimension\SetTargetedDimensionCommand;
use Src\SimExecution\Domain\Session\LearnerSessionRepository;
use Src\SimExecution\Domain\Session\SessionStatus;
use Src\Simulation\Application\Query\ListScenariosByProject\ListScenariosByProjectQuery;
use Src\Simulation\Application\Query\ListTasksByScenario\ListTasksByScenarioQuery;
use Src\Simulation\Domain\Scenario\ScenarioTemplate;
use Src\Simulation\Domain\Task\Task;
use Src\Submission\Application\Query\GetLatestSubmissionId\GetLatestSubmissionIdQuery;

/**
 * Implementation Plan §9.5 — updated to resolve two previously documented gaps:
 *
 * Gap 1 (BLD §11.4): After all scenario tasks are attempted, queries the
 *   HabitFlagRepository to find the dimension with the most unresolved
 *   observations in this session. If one is found, dispatches
 *   SetTargetedDimensionCommand so the next scenario onboarding can surface
 *   a "This scenario focuses on [X]" message to the learner.
 *
 * Gap 3 (Integration Spec §12 step 11): When findNextScenario() returns null
 *   (all scenarios done), marks the session Complete and dispatches
 *   GenerateFinalCompetencyGraphCommand instead of silently returning.
 */
final class ScenarioTransitionService
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly CommandBus $commandBus,
        private readonly LearnerSessionRepository $sessions,
        private readonly HabitFlagRepository $habitFlags,
    ) {}

    public function transitionIfScenarioComplete(string $learnerSessionId): void
    {
        $session = $this->sessions->findById($learnerSessionId);
        if ($session === null || $session->status() !== SessionStatus::Active || $session->currentScenarioId() === null) {
            return;
        }

        $liveTaskIds = $this->liveCoreTaskIds($session->currentScenarioId());
        if (! $this->allScenarioTasksAttempted($learnerSessionId, $liveTaskIds)) {
            return;
        }

        $this->commandBus->dispatch(new InjectQueuedSuggestionsCommand(
            learnerId:        $session->learnerId(),
            learnerSessionId: $learnerSessionId,
            targetSprintId:   $session->currentSprintId(),
        ));

        // BLD §7.3 — Complexity only escalates at scenario transitions, gated
        // on "consistent performance": every core task's latest submission in
        // the scenario just completed has to have passed.
        if ($this->allScenarioTasksPassed($learnerSessionId, $liveTaskIds)) {
            $this->commandBus->dispatch(new EscalateCacComplexityCommand($session->learnerId()));
        }

        // Gap 1 (BLD §11.4) — detect the dimension with the most unresolved
        // HabitFlag observations in this session and tag it on the session so
        // the next scenario onboarding can surface a targeted message.
        $dominantDimensionId = $this->habitFlags->findDominantWeaknessDimension(
            $session->learnerId(),
            $learnerSessionId,
        );
        if ($dominantDimensionId !== null) {
            $this->commandBus->dispatch(new SetTargetedDimensionCommand(
                learnerSessionId: $learnerSessionId,
                dimensionId:      $dominantDimensionId,
            ));
        }

        $nextScenario = $this->findNextScenario($session->projectId(), $session->currentScenarioId());

        // Gap 3 — project completion: no next scenario means all done.
        if ($nextScenario === null) {
            $session->markComplete();
            $this->sessions->save($session);
            $this->commandBus->dispatch(new GenerateFinalCompetencyGraphCommand(
                learnerId:        $session->learnerId(),
                learnerSessionId: $learnerSessionId,
            ));
            return;
        }

        $session->advanceToScenario($nextScenario->id());
        $this->sessions->save($session);
    }

    /** @return string[] */
    private function liveCoreTaskIds(string $scenarioId): array
    {
        /** @var Task[] $tasks */
        $tasks = $this->queryBus->ask(new ListTasksByScenarioQuery($scenarioId));
        $liveTaskIds = [];
        foreach ($tasks as $task) {
            $p = $task->toPrimitives();
            // Only core tasks are a blanket requirement — consequence/suggestion
            // tasks are conditional, injected onto some learners' boards but not
            // others', so requiring them here would incorrectly block scenario
            // completion for anyone who never triggered the injection.
            if ($p['is_published'] && $p['is_active'] && $p['task_type'] === 'core') {
                $liveTaskIds[] = $task->id();
            }
        }

        return $liveTaskIds;
    }

    /** @param string[] $liveTaskIds */
    private function allScenarioTasksAttempted(string $learnerSessionId, array $liveTaskIds): bool
    {
        if ($liveTaskIds === []) {
            return false;
        }

        foreach ($liveTaskIds as $taskId) {
            $submissionId = $this->queryBus->ask(new GetLatestSubmissionIdQuery($learnerSessionId, $taskId));
            if ($submissionId === null) {
                return false;
            }
        }

        return true;
    }

    /** @param string[] $liveTaskIds */
    private function allScenarioTasksPassed(string $learnerSessionId, array $liveTaskIds): bool
    {
        foreach ($liveTaskIds as $taskId) {
            $submissionId = $this->queryBus->ask(new GetLatestSubmissionIdQuery($learnerSessionId, $taskId));
            if ($submissionId === null) {
                return false;
            }

            $passed = $this->queryBus->ask(new GetSubmissionPassStatusQuery($submissionId));
            if ($passed !== true) {
                return false;
            }
        }

        return true;
    }

    private function findNextScenario(string $projectId, string $currentScenarioId): ?ScenarioTemplate
    {
        /** @var ScenarioTemplate[] $scenarios */
        $scenarios = $this->queryBus->ask(new ListScenariosByProjectQuery($projectId));

        $current = null;
        foreach ($scenarios as $scenario) {
            if ($scenario->id() === $currentScenarioId) {
                $current = $scenario;
                break;
            }
        }
        if ($current === null) {
            return null;
        }

        $next = null;
        foreach ($scenarios as $scenario) {
            if (! $scenario->isPublished() || ! $scenario->isActive() || $scenario->isDiagnostic()) {
                continue;
            }
            if ($scenario->sequenceOrder() <= $current->sequenceOrder()) {
                continue;
            }
            if ($next === null || $scenario->sequenceOrder() < $next->sequenceOrder()) {
                $next = $scenario;
            }
        }

        return $next;
    }
}
