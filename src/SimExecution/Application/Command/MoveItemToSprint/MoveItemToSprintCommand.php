<?php
namespace Src\SimExecution\Application\Command\MoveItemToSprint;

final class MoveItemToSprintCommand
{
    public function __construct(
        public readonly ?string $userId,
        public readonly string $itemId,
        public readonly string $sprintId,
    ) {}
}
