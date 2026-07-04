<?php

namespace Src\Simulation\Application\Query\ListVaultItemsByProject;

final class ListVaultItemsByProjectQuery
{
    public function __construct(public readonly string $projectId) {}
}
