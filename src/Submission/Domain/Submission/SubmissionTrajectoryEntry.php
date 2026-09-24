<?php
namespace Src\Submission\Domain\Submission;

/** One row per submission, chronological — the CAC-trajectory + rank-at-submission history. */
final class SubmissionTrajectoryEntry
{
    public function __construct(
        public readonly string $id,
        public readonly string $learnerSessionId,
        public readonly string $taskId,
        public readonly int $attemptNumber,
        public readonly string $cacComplexityAtSub,
        public readonly string $cacAutonomyAtSub,
        public readonly string $cacContextAtSub,
        public readonly string $rankAtSubmission,
        public readonly string $submittedAt,
    ) {}
}
