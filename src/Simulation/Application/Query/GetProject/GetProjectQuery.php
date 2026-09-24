<?php
namespace Src\Simulation\Application\Query\GetProject;

final class GetProjectQuery
{
    public function __construct(public readonly string $projectId) {}
}
