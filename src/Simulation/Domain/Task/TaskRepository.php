<?php
namespace Src\Simulation\Domain\Task;

interface TaskRepository
{
    public function save(Task $task): void;

    public function findById(TaskId $id): ?Task;

    /** @return Task[] */
    public function findByScenario(string $scenarioId): array;

    public function nextSequenceOrder(string $scenarioId): int;
}
