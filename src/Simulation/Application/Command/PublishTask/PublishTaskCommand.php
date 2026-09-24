<?php
namespace Src\Simulation\Application\Command\PublishTask;

final class PublishTaskCommand
{
    public function __construct(
        public readonly string $taskId,
        public readonly bool   $publish,
    ) {}
}
