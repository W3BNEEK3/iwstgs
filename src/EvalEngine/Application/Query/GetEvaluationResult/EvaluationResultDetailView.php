<?php
namespace Src\EvalEngine\Application\Query\GetEvaluationResult;

use Src\EvalEngine\Domain\Evaluation\DimensionEvaluationSummary;

final class EvaluationResultDetailView
{
    /** @param DimensionEvaluationSummary[] $dimensions */
    public function __construct(
        public readonly string $taskTitle,
        public readonly int $attemptNumber,
        public readonly string $overallTier,
        public readonly bool $passesThreshold,
        public readonly ?string $gapType,
        public readonly bool $isUncertain,
        public readonly ?string $followUpPromptText,
        public readonly array $dimensions,
        public readonly string $projectId,
    ) {}
}
