<?php
namespace Src\Simulation\Domain\Task;

// TaskDependencyLink — self-referential: this task depends on prerequisite_task_id
final class TaskDependencyLink
{
    public function __construct(
        private readonly string $id,
        private string $prerequisiteTaskId,
    ) {}

    public function id(): string { return $this->id; }

    public function prerequisiteTaskId(): string { return $this->prerequisiteTaskId; }

    public function toPrimitives(): array
    {
        return [
            'id'                  => $this->id,
            'prerequisite_task_id'=> $this->prerequisiteTaskId,
        ];
    }
}
