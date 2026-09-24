<?php

namespace Src\EvalEngine\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerModel;

class MismatchFlagModel extends Model
{
    use HasUuids;

    protected $table = 'mismatch_flags';

    public $timestamps = false;

    protected $fillable = [
        'id', 'learner_id', 'mismatch_type', 'detected_at', 'prompt_issued',
        'learner_response', 'resolution', 'resolved_at',
    ];

    protected $casts = [
        'detected_at'   => 'datetime',
        'prompt_issued' => 'boolean',
        'resolved_at'   => 'datetime',
    ];

    public function learner(): BelongsTo
    {
        return $this->belongsTo(LearnerModel::class, 'learner_id');
    }
}
