<?php

namespace Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\LearnerProfile\Domain\ConceptMastery\MasteryStatus;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ConceptTagModel;

class ConceptMasteryRecordModel extends Model
{
    use HasUuids;

    protected $table = 'concept_mastery_records';

    public $timestamps = false;

    protected $fillable = [
        'id', 'learner_id', 'concept_id', 'status',
        'tasks_encountered', 'tasks_met', 'last_updated_at',
    ];

    protected $casts = [
        'status'             => MasteryStatus::class,
        'tasks_encountered'  => 'integer',
        'tasks_met'          => 'integer',
        'last_updated_at'    => 'datetime',
    ];

    public function learner(): BelongsTo
    {
        return $this->belongsTo(LearnerModel::class, 'learner_id');
    }

    // One-way into Simulation, which owns the concept_tags vocabulary (BLD §5.4).
    public function conceptTag(): BelongsTo
    {
        return $this->belongsTo(ConceptTagModel::class, 'concept_id');
    }
}
