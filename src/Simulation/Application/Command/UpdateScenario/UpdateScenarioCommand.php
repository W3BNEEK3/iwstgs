<?php
namespace Src\Simulation\Application\Command\UpdateScenario;

final class UpdateScenarioCommand
{
    public function __construct(
        public readonly string $scenarioId,
        public readonly string $title,
    ) {}
}
