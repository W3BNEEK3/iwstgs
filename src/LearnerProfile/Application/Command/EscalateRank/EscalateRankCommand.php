<?php
namespace Src\LearnerProfile\Application\Command\EscalateRank;

final class EscalateRankCommand
{
    public function __construct(
        public readonly string $learnerId,
        public readonly string $triggerReason,
    ) {}
}
