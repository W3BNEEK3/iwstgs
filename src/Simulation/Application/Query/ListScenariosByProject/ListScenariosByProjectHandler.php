<?php
namespace Src\Simulation\Application\Query\ListScenariosByProject;

use Src\Simulation\Domain\Scenario\ScenarioTemplateRepository;

final class ListScenariosByProjectHandler
{
    public function __construct(private readonly ScenarioTemplateRepository $repository) {}

    /** @return \Src\Simulation\Domain\Scenario\ScenarioTemplate[] */
    public function handle(ListScenariosByProjectQuery $query): array
    {
        return $this->repository->findByProject($query->projectId);
    }
}
