<?php
namespace Src\Reporting\Application\Query\GetLearnerProfileDashboard;

/** One radar-chart axis — a canonical dimension merged with the learner's score, if any. */
final class ProfileDimensionView
{
    public function __construct(
        public readonly string $dimensionId,
        public readonly string $shortLabel,
        public readonly string $tier,
        public readonly int $evidenceCount,
    ) {}
}
