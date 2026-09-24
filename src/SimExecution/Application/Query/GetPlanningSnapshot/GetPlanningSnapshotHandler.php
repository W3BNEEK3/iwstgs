<?php
namespace Src\SimExecution\Application\Query\GetPlanningSnapshot;

use Src\SimExecution\Domain\Backlog\LearnerBacklogItemRepository;
use Src\SimExecution\Domain\Sprint\LearnerSprintRepository;

final class GetPlanningSnapshotHandler
{
    public function __construct(
        private readonly LearnerSprintRepository $sprints,
        private readonly LearnerBacklogItemRepository $backlogItems,
    ) {}

    public function handle(GetPlanningSnapshotQuery $query): PlanningSnapshotView
    {
        $sprint = $this->sprints->findLatestForSession($query->learnerSessionId);

        $items = array_map(
            fn ($item) => [
                'templateItemId' => $item->templateItemId(),
                'status'         => $item->status()->value,
                'priority'       => $item->priority()->value,
                'isInjected'     => $item->isInjected(),
            ],
            $this->backlogItems->findAllForSession($query->learnerSessionId),
        );

        return new PlanningSnapshotView(
            sprintId:     $sprint?->id(),
            sprintNumber: $sprint?->sprintNumber(),
            sprintGoal:   $sprint?->sprintGoal(),
            backlogItems: $items,
        );
    }
}
