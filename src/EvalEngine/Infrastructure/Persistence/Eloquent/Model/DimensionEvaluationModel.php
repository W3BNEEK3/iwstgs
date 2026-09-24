<?php

namespace Src\EvalEngine\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Competency\Infrastructure\Persistence\Eloquent\Model\CompetenceDimensionModel;
use Src\EvalEngine\Domain\Evaluation\OverallTier;

class DimensionEvaluationModel extends Model
{
    use HasUuids;

    protected $table = 'dimension_evaluations';

    public $timestamps = false;

    protected $fillable = [
        'id', 'evaluation_id', 'dimension_id', 'task_dimension_label',
        'tier_achieved', 'criteria_met', 'criteria_missed', 'layer_scores', 'evaluator_notes',
    ];

    protected $casts = [
        // tier_achieved uses the same four-value scale as evaluation_results.overall_tier —
        // OverallTier is reused rather than declaring a second identical enum.
        'tier_achieved'   => OverallTier::class,
        'criteria_met'    => 'array',
        'criteria_missed' => 'array',
        'layer_scores'    => 'array',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(EvaluationResultModel::class, 'evaluation_id');
    }

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(CompetenceDimensionModel::class, 'dimension_id', 'id');
    }
}
