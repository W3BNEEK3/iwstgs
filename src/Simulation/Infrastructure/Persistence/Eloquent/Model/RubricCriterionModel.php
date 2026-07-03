<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Competency\Infrastructure\Persistence\Eloquent\Model\CompetenceDimensionModel;

class RubricCriterionModel extends Model
{
    use HasUuids;

    protected $table = 'rubric_criteria';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'rubric_set_id',
        'task_id',
        'task_dimension_label',
        'parent_dimension_id',
        'complexity_level',
        'criterion_text',
        'weight',
        'dimension_weight',
        'claude_detection_hint',
        'distinguished_description',
        'proficient_description',
        'developing_description',
        'beginning_description',
        'is_architectural',
        'is_planning_layer',
        'reference_doc_anchor',
    ];

    protected $casts = [
        'weight'            => 'decimal:3',
        'dimension_weight'  => 'decimal:3',
        'is_architectural'  => 'boolean',
        'is_planning_layer' => 'boolean',
    ];

    public function rubricSet(): BelongsTo
    {
        return $this->belongsTo(RubricSetModel::class, 'rubric_set_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'task_id');
    }

    // Cross-context reference UP to Competency — allowed, because Simulation
    // legitimately depends on the competence vocabulary. Note the third arg:
    // the owner key is 'id' on a STRING primary key, not a UUID.
    public function parentDimension(): BelongsTo
    {
        return $this->belongsTo(CompetenceDimensionModel::class, 'parent_dimension_id', 'id');
    }
}
