<?php
namespace Src\Reporting\Application\Query\ListLearnerOverviews;

final class LearnerOverviewView
{
    public function __construct(
        public readonly string $learnerId,
        public readonly string $fullname,
        public readonly string $entryCategory,
        public readonly string $rankTier,
        public readonly int $rankLevel,
        public readonly int $failureStreak,
        public readonly bool $mismatchFlagActive,
        public readonly int $sessionCount,
    ) {}
}
