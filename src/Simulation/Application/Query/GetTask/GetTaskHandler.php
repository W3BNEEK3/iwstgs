<?php
namespace Src\Simulation\Application\Query\GetTask;

use Src\Simulation\Domain\Task\Task;
use Src\Simulation\Domain\Task\TaskId;
use Src\Simulation\Domain\Task\TaskRepository;

final class GetTaskHandler
{
    public function __construct(private readonly TaskRepository $repository) {}

    public function handle(GetTaskQuery $query): ?Task
    {
        return $this->repository->findById(TaskId::fromString($query->taskId));
    }
}
