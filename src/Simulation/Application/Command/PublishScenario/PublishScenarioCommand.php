<?php
namespace Src\Simulation\Application\Command\PublishScenario;

final class PublishScenarioCommand
{
    public function __construct(
        public readonly string $scenarioId,
        public readonly bool   $publish,
    ) {}
}
