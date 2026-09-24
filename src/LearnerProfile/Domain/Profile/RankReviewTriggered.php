<?php
namespace Src\LearnerProfile\Domain\Profile;

use Src\Shared\Domain\DomainEvent;

/**
 * Fired when failure_streak crosses the review threshold. Consumed to write
 * a rank_events row (event_type: review_triggered) and an aimediation_event
 * — the actual rank de-escalation decision itself is Phase 9 (Adaptive
 * Engine) territory; this only flags that a human/adaptive review is due.
 */
final class RankReviewTriggered extends DomainEvent
{
    public function __construct(
        public readonly string $learnerProfileId,
        public readonly string $learnerId,
        public readonly int $failureStreak,
    ) {
        parent::__construct();
    }
}
