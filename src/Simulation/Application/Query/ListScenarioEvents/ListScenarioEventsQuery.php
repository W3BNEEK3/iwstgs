<?php
namespace Src\Simulation\Application\Query\ListScenarioEvents;

final class ListScenarioEventsQuery
{
    public function __construct(public readonly string $scenarioId) {}
}
