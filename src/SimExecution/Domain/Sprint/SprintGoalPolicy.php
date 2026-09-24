<?php
namespace Src\SimExecution\Domain\Sprint;

/**
 * Integration Spec §15 (Sprint Goal Interaction by Rank), reproduced from
 * v1.0 unchanged:
 *   Junior-1 to Junior-2  -> fully pre-written, learner acknowledges
 *   Junior-3 to Mid-1     -> pre-written template with blanks, learner completes
 *   Mid-2 to Mid-3        -> blank, learner writes in full, system gives a quality hint
 *   Senior-1+             -> blank, no prompts
 *
 * Takes plain scalars rather than LearnerProfile's RankTier enum — SimExecution
 * doesn't depend on another bounded context's domain types, only on the plain
 * view LearnerProfile's GetLearnerRankQuery hands back.
 */
final class SprintGoalPolicy
{
    public static function resolve(string $rankTier, int $rankLevel): SprintGoalSource
    {
        if ($rankTier === 'Junior' && $rankLevel <= 2) {
            return SprintGoalSource::SystemDefined;
        }

        if (($rankTier === 'Junior' && $rankLevel === 3) || ($rankTier === 'Mid' && $rankLevel === 1)) {
            return SprintGoalSource::LearnerCompletedTemplate;
        }

        return SprintGoalSource::LearnerDefined;
    }

    /** Mid-2/Mid-3 get a system quality hint; Senior+ gets none, Junior tiers don't need one. */
    public static function showsQualityHint(string $rankTier, int $rankLevel): bool
    {
        return $rankTier === 'Mid' && $rankLevel >= 2;
    }
}
