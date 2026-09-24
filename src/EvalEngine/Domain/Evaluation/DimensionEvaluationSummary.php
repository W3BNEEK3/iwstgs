<?php
namespace Src\EvalEngine\Domain\Evaluation;

final class DimensionEvaluationSummary
{
    public function __construct(
        public readonly string $dimensionId,
        public readonly string $taskDimensionLabel,
        public readonly string $tierAchieved,
        public readonly array $criteriaMet,
        public readonly array $criteriaMissed,
        public readonly ?string $evaluatorNotes,
    ) {}
}
