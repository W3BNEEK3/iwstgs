<?php
namespace Src\EvalEngine\Domain\Evaluation;

use Src\Shared\Domain\DomainEvent;

/**
 * Fired by EvaluationService right after evaluation_results/dimension_evaluations
 * are persisted. PostEvaluationRouter (8.4) listens for this to mark tasks
 * done, trigger consequence handling, queue human review, and update
 * dimension_scores / failure_streak.
 */
final class EvaluationComplete extends DomainEvent
{
    /**
     * @param array<int, array{conceptId: ?string, met: bool}> $knowledgeAnchorsDetected
     * @param array<int, array{concept_name: string, domain: string, reason: string}> $conceptSuggestions
     *   Gap 4 — Claude's suggested new concepts from distinguished submissions.
     *   Empty array when not applicable.
     */
    public function __construct(
        public readonly string $evaluationId,
        public readonly string $submissionId,
        public readonly string $learnerId,
        public readonly string $learnerSessionId,
        public readonly string $taskId,
        public readonly string $overallTier,
        public readonly bool $passesThreshold,
        public readonly bool $isUncertain,
        public readonly ?string $gapType,
        public readonly array $knowledgeAnchorsDetected,
        public readonly array $conceptSuggestions = [],
    ) {
        parent::__construct();
    }
}
