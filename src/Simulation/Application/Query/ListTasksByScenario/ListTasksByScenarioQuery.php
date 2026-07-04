<?php
namespace Src\Simulation\Application\Query\ListTasksByScenario;

final class ListTasksByScenarioQuery
{
    public function __construct(public readonly string $scenarioId) {}
}
