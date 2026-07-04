<?php
namespace Src\Simulation\Application\Command\AddDependency;

use Src\Shared\Infrastructure\Id\UuidGenerator;
use Src\Simulation\Domain\Exceptions\TaskNotFoundException;
use Src\Simulation\Domain\Task\TaskDependencyLink;
use Src\Simulation\Domain\Task\TaskId;
use Src\Simulation\Domain\Task\TaskRepository;

final class AddDependencyHandler
{
    public function __construct(private readonly TaskRepository $repository) {}

    public function handle(AddDependencyCommand $command): void
    {
        $task = $this->repository->findById(TaskId::fromString($command->taskId));
        if ($task === null) {
            throw new TaskNotFoundException($command->taskId);
        }

        $task->addDependency(new TaskDependencyLink(
            id:                 (string) UuidGenerator::generate(),
            prerequisiteTaskId: $command->prerequisiteTaskId,
        ));

        $this->repository->save($task);
    }
}
