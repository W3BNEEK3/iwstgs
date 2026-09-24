<?php
namespace Src\LearnerProfile\Application\Command\AssignInitialRank;

final class AssignInitialRankCommand
{
    public function __construct(
        public readonly string $learnerId,
        public readonly string $rankTier,
        public readonly int $rankLevel,
        public readonly string $triggerReason,
        public readonly ?string $sourceSessionId,
    ) {}
}
