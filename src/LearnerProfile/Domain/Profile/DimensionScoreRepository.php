<?php
namespace Src\LearnerProfile\Domain\Profile;

/**
 * dimension_scores rows are seeded once (untested, 0 evidence) when the
 * profile bootstraps, then updated in place on every evaluation — an
 * upsert-shaped byproduct, not a full aggregate.
 */
interface DimensionScoreRepository
{
    /** Sets the dimension's current tier and increments its evidence_count by one. */
    public function recordEvidence(string $learnerId, string $dimensionId, DimensionTier $tier): void;

    /** @return DimensionScoreEntry[] every dimension this learner has a score row for */
    public function findAllForLearner(string $learnerId): array;
}
