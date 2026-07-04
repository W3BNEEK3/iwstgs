<?php
namespace Src\Simulation\Application\Command\AddDependency;

final class AddDependencyCommand
{
    public function __construct(
        public readonly string $taskId,
        public readonly string $prerequisiteTaskId,
    ) {}
}
