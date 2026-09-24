<?php
namespace Src\Simulation\Application\Command\RemoveReferenceMaterial;

use Src\Simulation\Domain\Exceptions\ScenarioNotFoundException;
use Src\Simulation\Domain\Scenario\ScenarioTemplateId;
use Src\Simulation\Domain\Scenario\ScenarioTemplateRepository;

final class RemoveReferenceMaterialHandler
{
    public function __construct(private readonly ScenarioTemplateRepository $repository) {}

    public function handle(RemoveReferenceMaterialCommand $command): void
    {
        $scenario = $this->repository->findById(ScenarioTemplateId::fromString($command->scenarioId));
        if ($scenario === null) {
            throw new ScenarioNotFoundException($command->scenarioId);
        }

        $scenario->removeReferenceMaterial($command->materialId);

        $this->repository->save($scenario);
    }
}
