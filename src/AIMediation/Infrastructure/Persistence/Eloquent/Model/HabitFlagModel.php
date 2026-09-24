<?php

namespace Src\AIMediation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\InjectedTaskCardModel;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel;

class HabitFlagModel extends Model
{
    use HasUuids;

    protected $table = 'habit_flags';

    // first_observed_at is the only timestamp column — not created_at/updated_at.
    public $timestamps = false;

    protected $fillable = [
        'id', 'learner_id', 'habit_description', 'source_task_id', 'first_observed_at',
        'observation_count', 'suggestion_task_id', 'is_resolved',
    ];

    protected $casts = [
        'first_observed_at' => 'datetime',
        'observation_count' => 'integer',
        'is_resolved'       => 'boolean',
    ];

    public function learner(): BelongsTo
    {
        return $this->belongsTo(LearnerModel::class, 'learner_id');
    }

    public function sourceTask(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'source_task_id');
    }

    // Column name predates this model (habit_flags.suggestion_task_id) but it actually
    // references injected_task_cards.id, not tasks.id — see the original migration.
    public function suggestionCard(): BelongsTo
    {
        return $this->belongsTo(InjectedTaskCardModel::class, 'suggestion_task_id');
    }
}
