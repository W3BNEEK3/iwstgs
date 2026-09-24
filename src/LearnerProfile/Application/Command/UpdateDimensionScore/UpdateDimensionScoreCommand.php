<?php
namespace Src\LearnerProfile\Application\Command\UpdateDimensionScore;

final class UpdateDimensionScoreCommand
{
    /** @param string $tierAchieved one of beginning|developing|proficient|distinguished */
    public function __construct(
        public readonly string $learnerId,
        public readonly string $dimensionId,
        public readonly string $tierAchieved,
    ) {}
}
