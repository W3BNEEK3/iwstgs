<?php

namespace Src\LearnerProfile\Domain\Profile;

use Src\Shared\Domain\DomainEvent;

final class LearnerProfileBootstrapped extends DomainEvent
{
    public function __construct(
        public readonly string $learnerProfileId,
        public readonly string $learnerId,
        public readonly string $rankTier,
        public readonly int    $rankLevel,
    ) {
        parent::__construct();
    }
}
