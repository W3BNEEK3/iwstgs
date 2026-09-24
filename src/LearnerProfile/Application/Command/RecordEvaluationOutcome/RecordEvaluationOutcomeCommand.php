<?php
namespace Src\LearnerProfile\Application\Command\RecordEvaluationOutcome;

final class RecordEvaluationOutcomeCommand
{
    public function __construct(
        public readonly string $learnerId,
        public readonly bool $passed,
    ) {}
}
