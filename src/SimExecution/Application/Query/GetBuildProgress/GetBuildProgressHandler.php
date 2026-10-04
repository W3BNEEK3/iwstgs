<?php
namespace Src\SimExecution\Application\Query\GetBuildProgress;

use Src\EvalEngine\Application\Query\GetSubmissionPassStatus\GetSubmissionPassStatusQuery;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Domain\Session\LearnerSessionRepository;
use Src\Simulation\Application\Query\ListScenariosByProject\ListScenariosByProjectQuery;
use Src\Simulation\Application\Query\ListTasksByScenario\ListTasksByScenarioQuery;
use Src\Simulation\Domain\Scenario\ScenarioTemplate;
use Src\Simulation\Domain\Task\Task;
use Src\Submission\Application\Query\CountSubmissionAttempts\CountSubmissionAttemptsQuery;
use Src\Submission\Application\Query\GetLatestSubmissionId\GetLatestSubmissionIdQuery;

/**
 * Where a learner is in a build: every milestone of the project in order
 * (chapters by sequence, milestones by sequence within them), which have
 * passed, and the one they're on now. Milestones unlock one at a time
 * (design doc v2-01 §6): the current one is the first that hasn't passed in
 * the current chapter.
 */
final class GetBuildProgressHandler
{
    public function __construct(
        private readonly LearnerSessionRepository $sessions,
        private readonly QueryBus $queryBus,
    ) {}

    public function handle(GetBuildProgressQuery $query): ?BuildProgressView
    {
        $session = $this->sessions->findById($query->learnerSessionId);
        if ($session === null) {
            return null;
        }

        /** @var ScenarioTemplate[] $scenarios */
        $scenarios = array_values(array_filter(
            $this->queryBus->ask(new ListScenariosByProjectQuery($session->projectId())),
            fn (ScenarioTemplate $s) => $s->isPublished() && $s->isActive() && ! $s->isDiagnostic(),
        ));
        usort($scenarios, fn ($a, $b) => $a->sequenceOrder() <=> $b->sequenceOrder());

        $milestones = [];
        $passed = [];
        $current = null;
        foreach ($scenarios as $scenario) {
            $tasks = array_values(array_filter(
                $this->queryBus->ask(new ListTasksByScenarioQuery($scenario->id())),
                fn (Task $t) => $t->toPrimitives()['task_type'] === 'milestone' && $t->isPublished() && $t->toPrimitives()['is_active'],
            ));
            usort($tasks, fn (Task $a, Task $b) => $a->toPrimitives()['sequence_order'] <=> $b->toPrimitives()['sequence_order']);

            foreach ($tasks as $task) {
                $latest = $this->queryBus->ask(new GetLatestSubmissionIdQuery($session->id(), $task->id()));
                $isPassed = $latest !== null && $this->queryBus->ask(new GetSubmissionPassStatusQuery($latest)) === true;
                if ($isPassed) {
                    $passed[] = $task->id();
                }

                $status = 'locked';
                if ($isPassed) {
                    $status = 'passed';
                } elseif ($current === null && $scenario->id() === $session->currentScenarioId()) {
                    $status = 'current';
                    $current = $task->id();
                }

                $milestones[] = [
                    'id'             => $task->id(),
                    'title'          => $task->toPrimitives()['title'],
                    'scenario_id'    => $scenario->id(),
                    'scenario_title' => $scenario->title(),
                    'status'         => $status,
                    'attempts'       => (int) $this->queryBus->ask(new CountSubmissionAttemptsQuery($session->id(), $task->id())),
                ];
            }
        }

        return new BuildProgressView($session->id(), $session->currentScenarioId(), $current, $milestones, $passed);
    }
}
