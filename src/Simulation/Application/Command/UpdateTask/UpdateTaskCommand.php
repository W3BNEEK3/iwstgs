<?php
namespace Src\Simulation\Application\Command\UpdateTask;

final class UpdateTaskCommand
{
    public function __construct(
        public readonly string $taskId,
        public readonly string $title,
        public readonly string $taskBrief,
    ) {}
}
