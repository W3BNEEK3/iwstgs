<?php
namespace Src\Simulation\Application\Command\AddKnowledgeAnchor;

use Src\Shared\Infrastructure\Id\UuidGenerator;
use Src\Simulation\Domain\Exceptions\TaskNotFoundException;
use Src\Simulation\Domain\Task\KnowledgeAnchor;
use Src\Simulation\Domain\Task\TaskId;
use Src\Simulation\Domain\Task\TaskRepository;

final class AddKnowledgeAnchorHandler
{
    public function __construct(private readonly TaskRepository $repository) {}

    public function handle(AddKnowledgeAnchorCommand $command): void
    {
        $task = $this->repository->findById(TaskId::fromString($command->taskId));
        if ($task === null) {
            throw new TaskNotFoundException($command->taskId);
        }

        $task->addKnowledgeAnchor(new KnowledgeAnchor(
            id:                     (string) UuidGenerator::generate(),
            conceptId:              $command->conceptId,
            conceptName:            $command->conceptName,
            domain:                 $command->domain,
            applicationExpectation: $command->applicationExpectation,
            isRequired:             $command->isRequired,
            remediationHint:        $command->remediationHint,
        ));

        $this->repository->save($task);
    }
}
