<?php
namespace Src\Simulation\Application\Query\GetDiagnosticScenario;

use Src\Simulation\Domain\Scenario\ScenarioTemplate;
use Src\Simulation\Domain\Scenario\ScenarioTemplateRepository;

final class GetDiagnosticScenarioHandler
{
    public function __construct(private readonly ScenarioTemplateRepository $repository) {}

    public function handle(GetDiagnosticScenarioQuery $query): ?ScenarioTemplate
    {
        return $this->repository->findDiagnosticScenario();
    }
}
