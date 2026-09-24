<?php
namespace Src\Reporting\Application\Query\GetLearnerProfileDashboard;

final class SessionHistoryEntry
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $projectId,
        public readonly string $projectTitle,
        public readonly string $status,
    ) {}
}
