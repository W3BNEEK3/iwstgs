<?php
namespace Src\Simulation\Application\Command\RemoveTaskChild;

use Src\Simulation\Domain\Exceptions\TaskNotFoundException;
use Src\Simulation\Domain\Task\TaskId;
use Src\Simulation\Domain\Task\TaskRepository;

final class RemoveTaskChildHandler
{
    public function __construct(private readonly TaskRepository $repository) {}

    public function handle(RemoveTaskChildCommand $command): void
    {
        $task = $this->repository->findById(TaskId::fromString($command->taskId));
        if ($task === null) {
            throw new TaskNotFoundException($command->taskId);
        }

        $task->removeChild($command->collection, $command->childId);

        $this->repository->save($task);
    }
}
