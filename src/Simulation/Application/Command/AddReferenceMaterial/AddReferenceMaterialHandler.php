<?php
namespace Src\Simulation\Application\Command\AddReferenceMaterial;

use Illuminate\Support\Str;
use Src\Simulation\Domain\Exceptions\ScenarioNotFoundException;
use Src\Simulation\Domain\Scenario\MaterialType;
use Src\Simulation\Domain\Scenario\ReferenceMaterial;
use Src\Simulation\Domain\Scenario\ScenarioTemplateId;
use Src\Simulation\Domain\Scenario\ScenarioTemplateRepository;

final class AddReferenceMaterialHandler
{
    public function __construct(private readonly ScenarioTemplateRepository $repository) {}

    public function handle(AddReferenceMaterialCommand $command): void
    {
        $scenario = $this->repository->findById(ScenarioTemplateId::fromString($command->scenarioId));
        if ($scenario === null) {
            throw new ScenarioNotFoundException($command->scenarioId);
        }

        $scenario->addReferenceMaterial(new ReferenceMaterial(
            id: (string) Str::uuid(),
            type: MaterialType::from($command->materialType),
            title: $command->title,
            content: $command->content,
            embeddedSignals: null,
            displayOrder: $command->displayOrder,
        ));

        $this->repository->save($scenario);
    }
}
