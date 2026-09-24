<?php
namespace Src\SimExecution\Application\Query\GetSprintPlanning;

final class GetSprintPlanningQuery
{
    public function __construct(
        public readonly ?string $userId,
        public readonly string $projectId,
    ) {}
}
