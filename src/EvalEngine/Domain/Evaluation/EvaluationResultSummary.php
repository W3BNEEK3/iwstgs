<?php
namespace Src\EvalEngine\Domain\Evaluation;

final class EvaluationResultSummary
{
    /** @param DimensionEvaluationSummary[] $dimensions */
    public function __construct(
        public readonly string $id,
        public readonly string $submissionId,
        public readonly string $learnerId,
        public readonly string $overallTier,
        public readonly bool $passesThreshold,
        public readonly ?string $gapType,
        public readonly bool $isUncertain,
        public readonly ?string $followUpPromptId,
        public readonly array $dimensions,
    ) {}
}
