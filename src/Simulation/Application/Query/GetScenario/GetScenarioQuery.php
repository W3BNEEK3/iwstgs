<?php
namespace Src\Simulation\Application\Query\GetScenario;

final class GetScenarioQuery
{
    public function __construct(public readonly string $scenarioId) {}
}
