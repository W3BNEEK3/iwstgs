<?php
namespace Src\Simulation\Application\Command\PublishTask;

use Src\Simulation\Domain\Exceptions\TaskNotFoundException;
use Src\Simulation\Domain\Task\TaskId;
use Src\Simulation\Domain\Task\TaskRepository;

final class PublishTaskHandler
{
    public function __construct(private readonly TaskRepository $repository) {}

    public function handle(PublishTaskCommand $command): void
    {
        $task = $this->repository->findById(TaskId::fromString($command->taskId));
        if ($task === null) {
            throw new TaskNotFoundException($command->taskId);
        }

        $command->publish ? $task->publish() : $task->unpublish();
        $this->repository->save($task);
    }
}
