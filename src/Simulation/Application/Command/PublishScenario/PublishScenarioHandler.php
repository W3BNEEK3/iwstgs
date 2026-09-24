<?php
namespace Src\Simulation\Application\Command\PublishScenario;

use Src\Simulation\Domain\Exceptions\ScenarioNotFoundException;
use Src\Simulation\Domain\Scenario\ScenarioTemplateId;
use Src\Simulation\Domain\Scenario\ScenarioTemplateRepository;

final class PublishScenarioHandler
{
    public function __construct(private readonly ScenarioTemplateRepository $repository) {}

    public function handle(PublishScenarioCommand $command): void
    {
        $scenario = $this->repository->findById(ScenarioTemplateId::fromString($command->scenarioId));
        if ($scenario === null) {
            throw new ScenarioNotFoundException($command->scenarioId);
        }

        $command->publish ? $scenario->publish() : $scenario->unpublish();

        $this->repository->save($scenario);
    }
}
