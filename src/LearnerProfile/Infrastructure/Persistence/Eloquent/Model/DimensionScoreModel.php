<?php

namespace Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Competency\Infrastructure\Persistence\Eloquent\Model\CompetenceDimensionModel;
use Src\LearnerProfile\Domain\Profile\DimensionTier;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerModel;

class DimensionScoreModel extends Model
{
    use HasUuids;

    protected $table = 'dimension_scores';

    public $timestamps = false;

    protected $fillable = [
        'id', 'learner_id', 'dimension_id', 'tier', 'evidence_count', 'last_updated_at',
    ];

    protected $casts = [
        'tier'             => DimensionTier::class,
        'evidence_count'   => 'integer',
        'last_updated_at'  => 'datetime',
    ];

    public function learner(): BelongsTo
    {
        return $this->belongsTo(LearnerModel::class, 'learner_id');
    }

    // One-way into Competency — the exact same rule Phase 3 set for
    // RubricCriterionModel::parentDimension(). Competency never points back.
    public function dimension(): BelongsTo
    {
        return $this->belongsTo(CompetenceDimensionModel::class, 'dimension_id');
    }
}
