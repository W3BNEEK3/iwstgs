<?php
namespace Src\Simulation\Application\Command\AddCacVariant;

use Src\Shared\Infrastructure\Id\UuidGenerator;
use Src\Simulation\Domain\Cac\CacLevel;
use Src\Simulation\Domain\Exceptions\TaskNotFoundException;
use Src\Simulation\Domain\Task\CacVariant;
use Src\Simulation\Domain\Task\TaskId;
use Src\Simulation\Domain\Task\TaskRepository;

final class AddCacVariantHandler
{
    public function __construct(private readonly TaskRepository $repository) {}

    public function handle(AddCacVariantCommand $command): void
    {
        $task = $this->repository->findById(TaskId::fromString($command->taskId));
        if ($task === null) {
            throw new TaskNotFoundException($command->taskId);
        }

        $task->addCacVariant(new CacVariant(
            id:                  (string) UuidGenerator::generate(),
            complexityLevel:     CacLevel::from($command->complexityLevel),
            scenarioText:        $command->scenarioText,
            scaffoldingTextLow:  $command->scaffoldingTextLow,
            scaffoldingTextMid:  $command->scaffoldingTextMid,
            scaffoldingTextHigh: $command->scaffoldingTextHigh,
            contextTextLow:      $command->contextTextLow,
            contextTextMid:      $command->contextTextMid,
            contextTextHigh:     $command->contextTextHigh,
        ));

        $this->repository->save($task);
    }
}
