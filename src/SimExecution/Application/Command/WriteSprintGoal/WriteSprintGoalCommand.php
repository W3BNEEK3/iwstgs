<?php
namespace Src\SimExecution\Application\Command\WriteSprintGoal;

final class WriteSprintGoalCommand
{
    public function __construct(
        public readonly ?string $userId,
        public readonly string $sprintId,
        public readonly string $goal,
    ) {}
}
