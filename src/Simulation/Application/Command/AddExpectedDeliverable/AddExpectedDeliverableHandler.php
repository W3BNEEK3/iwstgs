<?php
namespace Src\Simulation\Application\Command\AddExpectedDeliverable;

use Src\Shared\Infrastructure\Id\UuidGenerator;
use Src\Simulation\Domain\Exceptions\TaskNotFoundException;
use Src\Simulation\Domain\Task\DeliverableType;
use Src\Simulation\Domain\Task\ExpectedDeliverable;
use Src\Simulation\Domain\Task\TaskId;
use Src\Simulation\Domain\Task\TaskRepository;

final class AddExpectedDeliverableHandler
{
    public function __construct(private readonly TaskRepository $repository) {}

    public function handle(AddExpectedDeliverableCommand $command): void
    {
        $task = $this->repository->findById(TaskId::fromString($command->taskId));
        if ($task === null) {
            throw new TaskNotFoundException($command->taskId);
        }

        $task->addExpectedDeliverable(new ExpectedDeliverable(
            id:           (string) UuidGenerator::generate(),
            type:         DeliverableType::from($command->type),
            label:        $command->label,
            description:  $command->description,
            isRequired:   $command->isRequired,
            displayOrder: $command->displayOrder,
        ));

        $this->repository->save($task);
    }
}
