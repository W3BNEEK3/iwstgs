<?php
namespace Src\LearnerProfile\Application\Query\GetLearnerRank;

final class LearnerRankView
{
    public function __construct(
        public readonly string $rankTier,
        public readonly int $rankLevel,
        public readonly string $cacComplexity,
        public readonly string $cacAutonomy,
        public readonly string $cacContextFidelity,
        public readonly int $failureStreak = 0,
        public readonly bool $mismatchFlagActive = false,
    ) {}
}
