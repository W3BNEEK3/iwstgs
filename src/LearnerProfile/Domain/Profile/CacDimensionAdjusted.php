<?php
namespace Src\LearnerProfile\Domain\Profile;

use Src\Shared\Domain\DomainEvent;

/**
 * Fired whenever adjustCacFromSubmission() or
 * escalateComplexityForScenarioTransition() actually moves one of the three
 * CAC dimensions (BLD §7.3/§7.4). No consumer yet — recorded for the same
 * audit-trail reason RankReviewTriggered exists, ahead of any UI/reporting
 * need for it.
 */
final class CacDimensionAdjusted extends DomainEvent
{
    public function __construct(
        public readonly string $learnerProfileId,
        public readonly string $learnerId,
        public readonly string $dimension, // 'complexity' | 'autonomy' | 'context_fidelity'
        public readonly string $fromLevel,
        public readonly string $toLevel,
        public readonly string $reason, // e.g. 'success_streak' | 'scenario_transition' | 'scaffolding_on_escalation'
    ) {
        parent::__construct();
    }
}
