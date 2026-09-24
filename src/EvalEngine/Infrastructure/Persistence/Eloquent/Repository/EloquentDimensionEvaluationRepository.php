<?php

namespace Src\EvalEngine\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Str;
use Src\EvalEngine\Domain\Evaluation\DimensionEvaluationRepository;
use Src\EvalEngine\Infrastructure\Persistence\Eloquent\Model\DimensionEvaluationModel;

final class EloquentDimensionEvaluationRepository implements DimensionEvaluationRepository
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
    ): void {
        DimensionEvaluationModel::create([
            'id'                   => (string) Str::uuid(),
            'evaluation_id'        => $evaluationId,
            'dimension_id'         => $dimensionId,
            'task_dimension_label' => $taskDimensionLabel,
            'tier_achieved'        => $tierAchieved,
            'criteria_met'         => $criteriaMet,
            'criteria_missed'      => $criteriaMissed,
            'layer_scores'         => $layerScores,
            'evaluator_notes'      => $evaluatorNotes,
        ]);
    }

    public function findRecentTiersForDimension(string $learnerId, string $dimensionId, int $limit): array
    {
        return DimensionEvaluationModel::where('dimension_id', $dimensionId)
            ->whereHas('evaluation', fn ($q) => $q->where('learner_id', $learnerId))
            ->with('evaluation')
            ->get()
            ->sortByDesc(fn (DimensionEvaluationModel $d) => $d->evaluation->evaluated_at)
            ->take($limit)
            ->map(fn (DimensionEvaluationModel $d) => $d->tier_achieved->value)
            ->values()
            ->all();
    }
}
