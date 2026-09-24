<?php
namespace Src\SimExecution\Application\Query\GetLearnerSession;

use Src\SimExecution\Domain\Session\LearnerSessionRepository;

final class GetLearnerSessionHandler
{
    public function __construct(private readonly LearnerSessionRepository $sessions) {}

    public function handle(GetLearnerSessionQuery $query): ?LearnerSessionView
    {
        $session = $this->sessions->findById($query->sessionId);
        if ($session === null) {
            return null;
        }

        return new LearnerSessionView(
            id:                 $session->id(),
            learnerId:          $session->learnerId(),
            projectId:          $session->projectId(),
            status:             $session->status()->value,
            currentScenarioId:  $session->currentScenarioId(),
            currentSprintId:    $session->currentSprintId(),
        );
    }
}
