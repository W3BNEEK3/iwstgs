<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\SimExecution\Domain\Board\SprintBoardEventType;

class SprintBoardEventModel extends Model
{
    use HasUuids;

    protected $table = 'sprint_board_events';

    // Immutable audit log, created_at only — same convention as RankEventModel.
    public $timestamps = false;

    protected $fillable = [
        'id', 'learner_id', 'session_id', 'sprint_id', 'item_id',
        'event_type', 'from_status', 'to_status',
    ];

    protected $casts = [
        'event_type' => SprintBoardEventType::class,
    ];

    public function learner(): BelongsTo
    {
        return $this->belongsTo(LearnerModel::class, 'learner_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(LearnerSessionModel::class, 'session_id');
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(LearnerSprintModel::class, 'sprint_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(LearnerBacklogItemModel::class, 'item_id');
    }
}
