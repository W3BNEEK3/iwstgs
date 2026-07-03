<?php
namespace Src\Simulation\Application\Command\CreateScenario;

use Src\Simulation\Domain\Cac\CacLevel;
use Src\Simulation\Domain\Scenario\ScenarioTemplate;
use Src\Simulation\Domain\Scenario\ScenarioTemplateId;
use Src\Simulation\Domain\Scenario\ScenarioTemplateRepository;
use Src\Simulation\Domain\Scenario\SituationTriggerType;

final class CreateScenarioHandler
{
    public function __construct(private readonly ScenarioTemplateRepository $repository) {}

    public function handle(CreateScenarioCommand $command): void
    {
        $scenario = ScenarioTemplate::create(
            id: ScenarioTemplateId::generate(),
            projectId: $command->projectId,
            sequenceOrder: $this->repository->nextSequenceOrder($command->projectId),
            title: $command->title,
            narrativeContext: $command->narrativeContext,
            situationTrigger: $command->situationTrigger,
            situationTriggerType: SituationTriggerType::from($command->situationTriggerType),
            defaultAutonomyLevel: CacLevel::from($command->defaultAutonomyLevel),
            learnerRoleLabel: $command->learnerRoleLabel,
            isDiagnostic: $command->isDiagnostic,
        );

        $this->repository->save($scenario);

        foreach ($scenario->releaseEvents() as $event) {
            event($event);
        }
    }
}
