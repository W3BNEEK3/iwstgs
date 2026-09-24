<?php
namespace Src\Reporting\Application\Query\GetSessionSummary;

final class SessionSummaryView
{
    /** @param \Src\Submission\Domain\Submission\SubmissionTrajectoryEntry[] $submissions */
    public function __construct(
        public readonly string $sessionId,
        public readonly string $projectId,
        public readonly string $projectTitle,
        public readonly string $status,
        public readonly array $submissions,
    ) {}
}
