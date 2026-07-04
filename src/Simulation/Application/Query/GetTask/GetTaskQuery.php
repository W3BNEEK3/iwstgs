<?php
namespace Src\Simulation\Application\Query\GetTask;

final class GetTaskQuery
{
    public function __construct(public readonly string $taskId) {}
}
