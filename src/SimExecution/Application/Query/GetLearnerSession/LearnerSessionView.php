<?php
namespace Src\SimExecution\Application\Query\GetLearnerSession;

final class LearnerSessionView
{
    public function __construct(
        public readonly string $id,
        public readonly string $learnerId,
        public readonly string $projectId,
        public readonly string $status,
        public readonly ?string $currentScenarioId,
        public readonly ?string $currentSprintId,
    ) {}
}
