<?php
namespace Src\LearnerProfile\Domain\Rank;

final class RankEventSummary
{
    public function __construct(
        public readonly string $id,
        public readonly string $eventType,
        public readonly ?string $fromRankTier,
        public readonly ?int $fromRankLevel,
        public readonly string $toRankTier,
        public readonly int $toRankLevel,
        public readonly ?string $triggerReason,
        public readonly ?string $createdAt,
    ) {}
}
