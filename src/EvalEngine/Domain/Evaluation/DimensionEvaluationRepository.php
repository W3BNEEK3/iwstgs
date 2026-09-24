<?php
namespace Src\EvalEngine\Domain\Evaluation;

interface DimensionEvaluationRepository
{
    public function create(
        string $evaluationId,
        string $dimensionId,
        string $taskDimensionLabel,
        string $tierAchieved,
        array $criteriaMet,
        array $criteriaMissed,
        array $layerScores,
        ?string $evaluatorNotes,
    ): void;

    /** @return string[] tier_achieved values for this learner+dimension, most recent evaluation first */
    public function findRecentTiersForDimension(string $learnerId, string $dimensionId, int $limit): array;
}
