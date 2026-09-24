<?php
namespace Src\Simulation\Application\Command\CreateTask;

use Src\Simulation\Domain\Cac\CacLevel;
use Src\Simulation\Domain\Task\Task;
use Src\Simulation\Domain\Task\TaskId;
use Src\Simulation\Domain\Task\TaskRepository;
use Src\Simulation\Domain\Task\TaskType;

final class CreateTaskHandler
{
    public function __construct(private readonly TaskRepository $repository) {}

    public function handle(CreateTaskCommand $command): void
    {
        $task = Task::create(
            id:                  TaskId::generate(),
            scenarioId:          $command->scenarioId,
            sequenceOrder:       $this->repository->nextSequenceOrder($command->scenarioId),
            title:               $command->title,
            taskBrief:           $command->taskBrief,
            taskType:            TaskType::from($command->taskType),
            isCacRuntimeSet:     $command->isCacRuntimeSet,
            isArchitectural:     $command->isArchitectural,
            planningLayerActive: $command->planningLayerActive,
            domain:              $command->domain,
            roleTags:            $command->roleTags,
            tools:               $command->tools,
            prerequisiteConcepts:$command->prerequisiteConcepts,
            fixedComplexity:     $command->fixedComplexity ? CacLevel::from($command->fixedComplexity) : null,
            fixedAutonomy:       $command->fixedAutonomy ? CacLevel::from($command->fixedAutonomy) : null,
            fixedContextFidelity:$command->fixedContextFidelity ? CacLevel::from($command->fixedContextFidelity) : null,
            consequenceTaskIds:  $command->consequenceTaskIds,
            suggestionTaskIds:   $command->suggestionTaskIds,
            modelResponseSummary:$command->modelResponseSummary,
            timeLimitMinutes:    $command->timeLimitMinutes,
        );

        $this->repository->save($task);

        foreach ($task->releaseEvents() as $event) {
            event($event);
        }
    }
}
