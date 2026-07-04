<?php
namespace Src\Simulation\Application\Query\ListTasksByScenario;

use Src\Simulation\Domain\Task\TaskRepository;

final class ListTasksByScenarioHandler
{
    public function __construct(private readonly TaskRepository $repository) {}

    /** @return \Src\Simulation\Domain\Task\Task[] */
    public function handle(ListTasksByScenarioQuery $query): array
    {
        return $this->repository->findByScenario($query->scenarioId);
    }
}
