<?php

namespace Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Mapper;

use Src\LearnerProfile\Domain\Profile\CacLevel;
use Src\LearnerProfile\Domain\Profile\LearnerProfile;
use Src\LearnerProfile\Domain\Profile\LearnerProfileId;
use Src\LearnerProfile\Domain\Profile\RankTier;
use Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Model\LearnerProfileModel;

final class LearnerProfileMapper
{
    public function toEntity(LearnerProfileModel $m): LearnerProfile
    {
        return LearnerProfile::reconstitute(
            id:                 LearnerProfileId::fromString($m->id),
            learnerId:          $m->learner_id,
            currentRankTier:    $m->current_rank_tier instanceof RankTier ? $m->current_rank_tier : RankTier::from($m->current_rank_tier),
            currentRankLevel:   $m->current_rank_level,
            cacComplexity:      $m->cac_complexity instanceof CacLevel ? $m->cac_complexity : CacLevel::from($m->cac_complexity),
            cacAutonomy:        $m->cac_autonomy instanceof CacLevel ? $m->cac_autonomy : CacLevel::from($m->cac_autonomy),
            cacContextFidelity: $m->cac_context_fidelity instanceof CacLevel ? $m->cac_context_fidelity : CacLevel::from($m->cac_context_fidelity),
            mismatchFlagActive: (bool) $m->mismatch_flag_active,
            failureStreak:      $m->failure_streak,
            successStreak:      $m->success_streak,
        );
    }
}
