<?php

namespace Src\EvalEngine\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Competency\Infrastructure\Persistence\Eloquent\Model\CompetenceDimensionModel;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerModel;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerSessionModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel;

class GapFlagModel extends Model
{
    use HasUuids;

    protected $table = 'gap_flags';

    public $timestamps = false;

    protected $fillable = [
        'id', 'learner_id', 'dimension_id', 'sub_criterion_id', 'source_task_id',
        'source_session_id', 'is_resolved', 'resolved_at', 'created_at',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'created_at'  => 'datetime',
    ];

    public function learner(): BelongsTo
    {
        return $this->belongsTo(LearnerModel::class, 'learner_id');
    }

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(CompetenceDimensionModel::class, 'dimension_id', 'id');
    }

    public function sourceTask(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'source_task_id');
    }

    public function sourceSession(): BelongsTo
    {
        return $this->belongsTo(LearnerSessionModel::class, 'source_session_id');
    }
}
