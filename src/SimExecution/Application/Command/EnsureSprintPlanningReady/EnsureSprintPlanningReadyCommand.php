<?php
namespace Src\SimExecution\Application\Command\EnsureSprintPlanningReady;

final class EnsureSprintPlanningReadyCommand
{
    public function __construct(
        public readonly ?string $userId,
        public readonly string $projectId,
    ) {}
}
