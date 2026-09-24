<?php
namespace Src\Submission\Application\Service;

use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Query\GetPlanningSnapshot\GetPlanningSnapshotQuery;

/**
 * Reads the current sprint/backlog state for a session at the moment of
 * submission (Layer 4). Delegates the actual read to SimExecution via
 * QueryBus rather than depending on SimExecution's repositories directly —
 * Submission has no business reading another bounded context's domain
 * objects itself.
 */
final class PlanningSnapshotAssembler
{
    public function __construct(private readonly QueryBus $queryBus) {}

    public function assemble(string $learnerSessionId): array
    {
        $snapshot = $this->queryBus->ask(new GetPlanningSnapshotQuery($learnerSessionId));

        return [
            'sprint_id'     => $snapshot->sprintId,
            'sprint_number' => $snapshot->sprintNumber,
            'sprint_goal'   => $snapshot->sprintGoal,
            'backlog_items' => $snapshot->backlogItems,
        ];
    }
}
