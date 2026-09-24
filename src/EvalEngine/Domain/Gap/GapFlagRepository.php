<?php
namespace Src\EvalEngine\Domain\Gap;

/**
 * gap_flags has no domain aggregate — created by ConsequenceTaskInjector on
 * a failing dimension, later marked resolved by a simple update when the
 * learner passes a follow-up task on that same dimension (same upsert-ish
 * shape as dimension_scores/concept_mastery_records in Phase 8, not a full
 * aggregate).
 */
interface GapFlagRepository
{
    public function create(
        string $learnerId,
        string $dimensionId,
        ?string $subCriterionId,
        string $sourceTaskId,
        string $sourceSessionId,
    ): string;

    /** Marks every still-open gap flag for this learner+dimension as resolved. */
    public function resolveOpenForDimension(string $learnerId, string $dimensionId): void;

    /** @return GapFlagSummary[] every gap flag (open and resolved) ever raised for this learner */
    public function findAllForLearner(string $learnerId): array;
}
