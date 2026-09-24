<?php

namespace Src\LearnerProfile\Domain\Profile;

/**
 * Not an entity — it has no identity of its own outside the (learner_id, dimension_id)
 * pair the database already enforces as unique. Exists only to carry the six starting
 * scores out of LearnerProfile::bootstrap() and into the repository's transaction.
 */
final class DimensionScoreEntry
{
    public function __construct(
        public readonly string $dimensionId,
        public readonly DimensionTier $tier,
        public readonly int $evidenceCount,
    ) {}
}
