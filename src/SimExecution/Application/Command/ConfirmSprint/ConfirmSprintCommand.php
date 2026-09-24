<?php
namespace Src\SimExecution\Application\Command\ConfirmSprint;

final class ConfirmSprintCommand
{
    public function __construct(
        public readonly ?string $userId,
        public readonly string $sprintId,
    ) {}
}
