<?php
namespace Src\SimExecution\Application\Query\GetDiagnosticBoard;

use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Domain\Diagnostic\DiagnosticSessionRepository;
use Src\SimExecution\Domain\Diagnostic\DiagnosticStatus;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\SimExecution\Domain\Session\LearnerSessionRepository;
use Src\Simulation\Application\Query\GetDiagnosticScenario\GetDiagnosticScenarioQuery;
use Src\Simulation\Application\Query\ListTasksByScenario\ListTasksByScenarioQuery;
use Src\SimExecution\Application\Query\GetSprintBoard\ReferenceMaterialBoardView;
use Src\Simulation\Domain\Scenario\ReferenceMaterial;
use Src\Simulation\Domain\Scenario\ScenarioTemplate;
use Src\Simulation\Domain\Task\Task;
use Src\Submission\Application\Query\CountSubmissionAttempts\CountSubmissionAttemptsQuery;

final class GetDiagnosticBoardHandler
{
    public function __construct(
        private readonly LearnerRepository $learners,
        private readonly LearnerSessionRepository $sessions,
        private readonly DiagnosticSessionRepository $diagnosticSessions,
        private readonly QueryBus $queryBus,
    ) {}

    public function handle(GetDiagnosticBoardQuery $query): ?DiagnosticBoardView
    {
        $learner = $query->userId !== null ? $this->learners->findByUserId($query->userId) : null;
        if ($learner === null) {
            return null;
        }

        $diagnostic = $this->diagnosticSessions->findLatestForLearner($learner->id());
        if ($diagnostic === null) {
            return null;
        }

        /** @var ScenarioTemplate|null $scenario */
        $scenario = $this->queryBus->ask(new GetDiagnosticScenarioQuery());
        if ($scenario === null) {
            return null;
        }

        $session = $this->sessions->findByLearnerAndProject($learner->id(), $scenario->projectId());
        if ($session === null) {
            return null;
        }

        /** @var Task[] $tasks */
        $tasks = $this->queryBus->ask(new ListTasksByScenarioQuery($scenario->id()));

        $taskViews = [];
        foreach ($tasks as $task) {
            $p = $task->toPrimitives();
            if (! $p['is_published'] || ! $p['is_active']) {
                continue;
            }

            $taskViews[] = new DiagnosticTaskView(
                taskId:           $task->id(),
                title:            $p['title'],
                taskBrief:        $p['task_brief'],
                submissionCount:  $this->queryBus->ask(new CountSubmissionAttemptsQuery($session->id(), $task->id())),
            );
        }

        return new DiagnosticBoardView(
            sessionId:          $session->id(),
            scenarioTitle:      $scenario->title(),
            narrativeContext:   $scenario->narrativeContext(),
            situationTrigger:   $scenario->situationTrigger(),
            tasks:              $taskViews,
            isComplete:         $diagnostic->status === DiagnosticStatus::Complete,
            assignedRankTier:   $diagnostic->assignedRankTier,
            assignedRankLevel:  $diagnostic->assignedRankLevel,
            referenceMaterials: array_map(
                fn (ReferenceMaterial $m) => new ReferenceMaterialBoardView($m->type()->value, $m->title(), $m->content()),
                $scenario->referenceMaterials(),
            ),
        );
    }
}
