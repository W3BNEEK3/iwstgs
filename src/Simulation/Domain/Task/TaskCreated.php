<?php
namespace Src\Simulation\Domain\Task;

use Src\Shared\Domain\DomainEvent;

final class TaskCreated extends DomainEvent
{
    public function __construct(
        public readonly string $taskId,
        public readonly string $scenarioId,
        public readonly string $title,
    ) {
        parent::__construct();
    }
}
