<?php
namespace Src\SimExecution\Application\Query\GetPlanningSnapshot;

final class GetPlanningSnapshotQuery
{
    public function __construct(public readonly string $learnerSessionId) {}
}
