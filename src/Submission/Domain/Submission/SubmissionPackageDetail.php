<?php
namespace Src\Submission\Domain\Submission;

final class SubmissionPackageDetail
{
    public function __construct(
        public readonly string $id,
        public readonly string $learnerSessionId,
        public readonly string $learnerId,
        public readonly string $taskId,
        public readonly string $scenarioId,
        public readonly ?string $sprintId,
        public readonly int $attemptNumber,
        public readonly ?string $layer1Text,
        public readonly ?array $layer2ArtifactIds,
        public readonly ?string $layer3Code,
        public readonly ?array $layer4PlanningSnapshot,
        public readonly string $cacComplexityAtSub,
        public readonly string $cacAutonomyAtSub,
        public readonly string $cacContextAtSub,
        public readonly string $rankAtSubmission,
    ) {}
}
