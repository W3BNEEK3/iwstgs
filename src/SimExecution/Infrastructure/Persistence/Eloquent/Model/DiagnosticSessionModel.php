<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\SimExecution\Domain\Diagnostic\DiagnosticPathway;
use Src\SimExecution\Domain\Diagnostic\DiagnosticStatus;

class DiagnosticSessionModel extends Model
{
    use HasUuids;

    protected $table = 'diagnostic_sessions';

    public $timestamps = false;

    protected $fillable = [
        'id', 'learner_id', 'pathway', 'status',
        'assigned_rank_tier', 'assigned_rank_level', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'pathway'              => DiagnosticPathway::class,
        'status'               => DiagnosticStatus::class,
        'assigned_rank_level'  => 'integer',
        'started_at'           => 'datetime',
        'completed_at'         => 'datetime',
    ];

    public function learner(): BelongsTo
    {
        return $this->belongsTo(LearnerModel::class, 'learner_id');
    }
}
