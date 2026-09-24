<?php
namespace Src\SimExecution\Application\Query\ListSessionsForLearner;

use Src\SimExecution\Application\Query\GetLearnerSession\LearnerSessionView;
use Src\SimExecution\Domain\Session\LearnerSession;
use Src\SimExecution\Domain\Session\LearnerSessionRepository;

final class ListSessionsForLearnerHandler
{
    public function __construct(private readonly LearnerSessionRepository $sessions) {}

    /** @return LearnerSessionView[] */
    public function handle(ListSessionsForLearnerQuery $query): array
    {
        return array_map(
            fn (LearnerSession $s) => new LearnerSessionView(
                id:                 $s->id(),
                learnerId:          $s->learnerId(),
                projectId:          $s->projectId(),
                status:             $s->status()->value,
                currentScenarioId:  $s->currentScenarioId(),
                currentSprintId:    $s->currentSprintId(),
            ),
            $this->sessions->findAllForLearner($query->learnerId),
        );
    }
}
