<?php
namespace Src\Simulation\Application\Command\UpdateScenario;

use Src\Simulation\Domain\Exceptions\ScenarioNotFoundException;
use Src\Simulation\Domain\Scenario\ScenarioTemplateId;
use Src\Simulation\Domain\Scenario\ScenarioTemplateRepository;

final class UpdateScenarioHandler
{
    public function __construct(private readonly ScenarioTemplateRepository $repository) {}

    public function handle(UpdateScenarioCommand $command): void
    {
        $scenario = $this->repository->findById(ScenarioTemplateId::fromString($command->scenarioId));
        if ($scenario === null) {
            throw new ScenarioNotFoundException($command->scenarioId);
        }

        $scenario->rename($command->title);

        $this->repository->save($scenario);
    }
}
