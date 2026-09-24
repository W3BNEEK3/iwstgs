<?php
namespace Src\AIMediation\Application\Command\RecordConceptTagRecommendation;

/**
 * Gap 4 — Dispatched by PostEvaluationRouter when a 'distinguished' submission
 * returns concept_suggestions from Claude. Each suggestion is recorded in
 * concept_tag_recommendations for curator review.
 */
final class RecordConceptTagRecommendationCommand
{
    /** @param array<int, array{concept_name: string, domain: string, reason: string}> $suggestions */
    public function __construct(
        public readonly string $submissionId,
        public readonly string $taskId,
        public readonly string $learnerId,
        public readonly array  $suggestions,
    ) {}
}
