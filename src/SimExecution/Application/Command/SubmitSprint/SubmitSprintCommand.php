<?php
namespace Src\SimExecution\Application\Command\SubmitSprint;

final class SubmitSprintCommand
{
    public function __construct(
        public readonly ?string $userId,
        public readonly string $sprintId,
    ) {}
}
