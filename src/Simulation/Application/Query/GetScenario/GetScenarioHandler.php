<?php
namespace Src\Simulation\Application\Query\GetScenario;

use Src\Simulation\Domain\Scenario\ScenarioTemplate;
use Src\Simulation\Domain\Scenario\ScenarioTemplateId;
use Src\Simulation\Domain\Scenario\ScenarioTemplateRepository;

final class GetScenarioHandler
{
    public function __construct(private readonly ScenarioTemplateRepository $repository) {}

    public function handle(GetScenarioQuery $query): ?ScenarioTemplate
    {
        return $this->repository->findById(ScenarioTemplateId::fromString($query->scenarioId));
    }
}
