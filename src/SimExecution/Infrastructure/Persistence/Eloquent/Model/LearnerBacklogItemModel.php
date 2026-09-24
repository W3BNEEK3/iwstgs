<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\SimExecution\Domain\Backlog\BacklogItemStatus;
use Src\SimExecution\Domain\Backlog\BacklogPriority;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\BacklogItemTemplateModel;

class LearnerBacklogItemModel extends Model
{
    use HasUuids;

    protected $table = 'learner_backlog_items';

    // moved_to_sprint_at / completed_at are the timestamp columns here — not created_at/updated_at.
    public $timestamps = false;

    protected $fillable = [
        'id', 'learner_session_id', 'learner_id', 'template_item_id', 'sprint_id',
        'status', 'priority', 'moved_to_sprint_at', 'completed_at', 'learner_notes',
        'is_injected', 'injected_card_id',
    ];

    protected $casts = [
        'status'             => BacklogItemStatus::class,
        'priority'           => BacklogPriority::class,
        'moved_to_sprint_at' => 'datetime',
        'completed_at'       => 'datetime',
        'is_injected'        => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(LearnerSessionModel::class, 'learner_session_id');
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(LearnerModel::class, 'learner_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(BacklogItemTemplateModel::class, 'template_item_id');
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(LearnerSprintModel::class, 'sprint_id');
    }
}
