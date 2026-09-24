<?php
namespace Src\EvalEngine\Domain\Evaluation;

final class TaskPerformanceSummary
{
    /** @param array<string, int> $tierCounts overall_tier value => count */
    public function __construct(
        public readonly string $taskId,
        public readonly int $attemptCount,
        public readonly int $passCount,
        public readonly array $tierCounts,
    ) {}
}
