<?php
namespace Src\LearnerProfile\Domain\ConceptMastery;

final class ConceptMasterySummary
{
    public function __construct(
        public readonly string $conceptId,
        public readonly string $status,
        public readonly int $tasksEncountered,
        public readonly int $tasksMet,
    ) {}
}
