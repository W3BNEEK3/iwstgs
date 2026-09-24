<?php
namespace Src\LearnerProfile\Domain\Rank;

/**
 * rank_events has no domain aggregate — write-once audit log, same
 * reasoning as sprint_board_events/aimediation_events.
 */
interface RankEventRepository
{
    public function record(
        string $learnerId,
        RankEventType $eventType,
        ?string $fromRankTier,
        ?int $fromRankLevel,
        ?string $toRankTier,
        ?int $toRankLevel,
        ?string $triggerReason,
        ?string $sourceSessionId,
    ): void;

    /**
     * Updates the placeholder initial_assignment row in place (written the
     * moment the profile bootstrapped — see EloquentLearnerProfileRepository)
     * rather than inserting a second one. There is exactly one
     * initial_assignment row per learner, ever.
     */
    public function updateInitialAssignment(
        string $learnerId,
        string $toRankTier,
        int $toRankLevel,
        string $triggerReason,
        ?string $sourceSessionId,
    ): void;

    /** @return RankEventSummary[] chronological, oldest first */
    public function findAllForLearner(string $learnerId): array;
}
