<?php
namespace Src\SimExecution\Application\Query\GetSprintPlanning;

final class SprintPlanningView
{
    /**
     * @param BacklogItemPlanningView[] $backlogItems items not yet in this sprint
     * @param BacklogItemPlanningView[] $sprintItems   items already moved into this sprint
     */
    public function __construct(
        public readonly string $sprintId,
        public readonly int $sprintNumber,
        public readonly ?string $sprintGoal,
        public readonly string $sprintGoalSource,
        public readonly bool $showsQualityHint,
        public readonly array $backlogItems,
        public readonly array $sprintItems,
        public readonly int $scenarioSequence = 1,
    ) {}
}
