<?php
namespace Src\Simulation\Application\Query\ListCriteriaByTask;

final class ListCriteriaByTaskQuery
{
    public function __construct(public readonly string $taskId) {}
}
