<?php
namespace Src\Simulation\Application\Command\AddGuidancePrompt;

use Src\Shared\Infrastructure\Id\UuidGenerator;
use Src\Simulation\Domain\Cac\CacLevel;
use Src\Simulation\Domain\Exceptions\TaskNotFoundException;
use Src\Simulation\Domain\Task\DeliveryMode;
use Src\Simulation\Domain\Task\GuidancePrompt;
use Src\Simulation\Domain\Task\TaskId;
use Src\Simulation\Domain\Task\TaskRepository;

final class AddGuidancePromptHandler
{
    public function __construct(private readonly TaskRepository $repository) {}

    public function handle(AddGuidancePromptCommand $command): void
    {
        $task = $this->repository->findById(TaskId::fromString($command->taskId));
        if ($task === null) {
            throw new TaskNotFoundException($command->taskId);
        }

        $task->addGuidancePrompt(new GuidancePrompt(
            id:                  (string) UuidGenerator::generate(),
            triggerDimension:    $command->triggerDimension,
            promptText:          $command->promptText,
            autonomyLevelFilter: $command->autonomyLevelFilter ? CacLevel::from($command->autonomyLevelFilter) : null,
            deliveryMode:        DeliveryMode::from($command->deliveryMode),
            displayOrder:        $command->displayOrder,
        ));

        $this->repository->save($task);
    }
}
