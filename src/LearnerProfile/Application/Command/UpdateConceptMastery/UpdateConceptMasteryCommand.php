<?php
namespace Src\LearnerProfile\Application\Command\UpdateConceptMastery;

final class UpdateConceptMasteryCommand
{
    public function __construct(
        public readonly string $learnerId,
        public readonly string $conceptId,
        public readonly bool $met,
    ) {}
}
