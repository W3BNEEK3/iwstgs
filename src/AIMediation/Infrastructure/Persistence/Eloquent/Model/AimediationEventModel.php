<?php

namespace Src\AIMediation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\AIMediation\Domain\Audit\ActionTaken;
use Src\AIMediation\Domain\Audit\TriggerType;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerModel;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerSessionModel;

class AimediationEventModel extends Model
{
    use HasUuids;

    protected $table = 'aimediation_events';

    // Immutable audit log, created_at only — same convention as rank_events/sprint_board_events.
    public $timestamps = false;

    protected $fillable = [
        'id', 'learner_id', 'session_id', 'trigger_type', 'trigger_source_id',
        'action_taken', 'action_detail', 'is_deterministic', 'confidence_score',
    ];

    protected $casts = [
        'trigger_type'      => TriggerType::class,
        'action_taken'      => ActionTaken::class,
        'action_detail'     => 'array',
        'is_deterministic'  => 'boolean',
        'confidence_score'  => 'decimal:3',
    ];

    public function learner(): BelongsTo
    {
        return $this->belongsTo(LearnerModel::class, 'learner_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(LearnerSessionModel::class, 'session_id');
    }
}
