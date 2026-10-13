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
        /** v2 Build milestones: the reviewer's notes and the test run are shown to the learner. */
        public readonly bool $isFromRepository = false,
        public readonly ?string $ciStatus = null,
        public readonly ?string $ciSummary = null,
        public readonly ?string $commitSha = null,
        public readonly ?string $learnerSessionId = null,
        public readonly ?string $taskId = null,
    ) {}
}
