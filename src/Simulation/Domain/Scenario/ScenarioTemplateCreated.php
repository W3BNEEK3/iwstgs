<?php
namespace Src\Simulation\Domain\Scenario;

use Src\Shared\Domain\DomainEvent;

final class ScenarioTemplateCreated extends DomainEvent
{
    public function __construct(
        public readonly string $scenarioId,
        public readonly string $projectId,
        public readonly string $title,
    ) {
        parent::__construct();
    }
}
