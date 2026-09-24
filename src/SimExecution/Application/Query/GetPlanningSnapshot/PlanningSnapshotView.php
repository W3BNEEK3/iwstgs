<?php
namespace Src\SimExecution\Application\Query\GetPlanningSnapshot;

final class PlanningSnapshotView
{
    /** @param array<int, array{templateItemId: string, status: string, priority: string, isInjected: bool}> $backlogItems */
    public function __construct(
        public readonly ?string $sprintId,
        public readonly ?int $sprintNumber,
        public readonly ?string $sprintGoal,
        public readonly array $backlogItems,
    ) {}
}
