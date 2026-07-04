<?php
namespace Src\Simulation\Application\Command\UpdateTask;

use Src\Simulation\Domain\Exceptions\TaskNotFoundException;
use Src\Simulation\Domain\Task\TaskId;
use Src\Simulation\Domain\Task\TaskRepository;

final class UpdateTaskHandler
{
    public function __construct(private readonly TaskRepository $repository) {}

    public function handle(UpdateTaskCommand $command): void
    {
        $task = $this->repository->findById(TaskId::fromString($command->taskId));
        if ($task === null) {
            throw new TaskNotFoundException($command->taskId);
        }

        $task->rename($command->title);

        $this->repository->save($task);
    }
}
