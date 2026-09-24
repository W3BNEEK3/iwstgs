<?php
namespace Src\LearnerProfile\Application\Command\UpdateDimensionScore;

use Src\LearnerProfile\Domain\Profile\DimensionScoreRepository;
use Src\LearnerProfile\Domain\Profile\DimensionTier;

/**
 * EvalEngine's four-tier evaluation scale (beginning/developing/proficient/
 * distinguished) doesn't share vocabulary with LearnerProfile's own
 * dimension tier scale (untested/basic/intermediate/advanced) — both are
 * four-value scales, so this maps positionally (ordering is what matters
 * for "current competency level"), with distinguished folding onto the
 * same top tier as proficient since DimensionTier has no fifth value.
 */
final class UpdateDimensionScoreHandler
{
    private const TIER_MAP = [
        'beginning'     => DimensionTier::Basic,
        'developing'    => DimensionTier::Intermediate,
        'proficient'    => DimensionTier::Advanced,
        'distinguished' => DimensionTier::Advanced,
    ];

    public function __construct(private readonly DimensionScoreRepository $dimensionScores) {}

    public function handle(UpdateDimensionScoreCommand $command): void
    {
        $tier = self::TIER_MAP[$command->tierAchieved] ?? null;
        if ($tier === null) {
            return;
        }

        $this->dimensionScores->recordEvidence($command->learnerId, $command->dimensionId, $tier);
    }
}
