<?php
namespace Src\Simulation\Application\Query\ListScenariosByProject;

final class ListScenariosByProjectQuery
{
    public function __construct(public readonly string $projectId) {}
}
