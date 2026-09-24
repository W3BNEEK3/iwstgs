<?php

namespace Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\LearnerProfile\Domain\Rank\RankEventType;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerModel;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerSessionModel;

class RankEventModel extends Model
{
    use HasUuids;

    protected $table = 'rank_events';

    // Immutable audit log, created_at only. There is no updated_at column for Eloquent
    // to try to write to, which is exactly why this must be false rather than true.
    public $timestamps = false;

    protected $fillable = [
        'id', 'learner_id', 'event_type', 'from_rank_tier', 'from_rank_level',
        'to_rank_tier', 'to_rank_level', 'trigger_reason', 'source_session_id',
    ];

    protected $casts = [
        'event_type'       => RankEventType::class,
        'from_rank_level'  => 'integer',
        'to_rank_level'    => 'integer',
    ];

    public function learner(): BelongsTo
    {
        return $this->belongsTo(LearnerModel::class, 'learner_id');
    }

    // Nullable per 5a Reconciliation #2 — initial_assignment fires before any session exists.
    public function sourceSession(): BelongsTo
    {
        return $this->belongsTo(LearnerSessionModel::class, 'source_session_id');
    }
}
