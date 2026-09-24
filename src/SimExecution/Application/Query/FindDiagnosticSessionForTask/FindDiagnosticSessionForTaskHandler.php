<?php
namespace Src\SimExecution\Application\Query\FindDiagnosticSessionForTask;

use Src\SimExecution\Domain\Diagnostic\DiagnosticSessionRepository;
use Src\SimExecution\Domain\Session\LearnerSessionRepository;
use Src\SimExecution\Domain\Session\SessionStatus;
use Src\Simulation\Application\Query\GetTask\GetTaskQuery;
use Src\Simulation\Domain\Task\Task;
use Src\Shared\Application\Bus\QueryBus;

/**
 * Nullable-return "does this evaluated task belong to a diagnostic run"
 * check, mirroring FindBacklogItemForTaskQuery's shape — used by
 * PostEvaluationRouter to route diagnostic submissions to RankAssignmentService
 * instead of the normal backlog/consequence/suggestion routing.
 */
final class FindDiagnosticSessionForTaskHandler
{
    public function __construct(
        private readonly LearnerSessionRepository $sessions,
        private readonly DiagnosticSessionRepository $diagnosticSessions,
        private readonly QueryBus $queryBus,
    ) {}

    public function handle(FindDiagnosticSessionForTaskQuery $query): ?string
    {
        $session = $this->sessions->findById($query->learnerSessionId);
        if ($session === null || $session->status() !== SessionStatus::Diagnostic) {
            return null;
        }

        /** @var Task|null $task */
        $task = $this->queryBus->ask(new GetTaskQuery($query->taskId));
        if ($task === null || $task->toPrimitives()['scenario_id'] !== $session->currentScenarioId()) {
            return null;
        }

        $diagnostic = $this->diagnosticSessions->findInProgressForLearner($session->learnerId());
        return $diagnostic?->id;
    }
}
