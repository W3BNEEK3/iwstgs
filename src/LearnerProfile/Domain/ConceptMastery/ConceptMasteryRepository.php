<?php
namespace Src\LearnerProfile\Domain\ConceptMastery;

/**
 * concept_mastery_records rows don't get pre-seeded per learner (unlike
 * dimension_scores) — a concept with no row simply hasn't been encountered
 * yet. The first recordEncounter() call creates it.
 */
interface ConceptMasteryRepository
{
    public function recordEncounter(string $learnerId, string $conceptId, bool $met): void;

    /** @return ConceptMasterySummary[] every concept this learner has encountered */
    public function findAllForLearner(string $learnerId): array;
}
