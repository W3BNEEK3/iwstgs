<?php
namespace Src\Simulation\Application\Query\ListBacklogItemTemplatesByProject;

final class ListBacklogItemTemplatesByProjectQuery
{
    public function __construct(public readonly string $projectId) {}
}
