<?php
namespace Src\Reporting\Application\Query\ListTaskPerformance;

final class TaskPerformanceView
{
    /** @param array<string, int> $tierCounts */
    public function __construct(
        public readonly string $taskId,
        public readonly string $taskTitle,
        public readonly int $attemptCount,
        public readonly int $passCount,
        public readonly float $passRate,
        public readonly array $tierCounts,
    ) {}
}
