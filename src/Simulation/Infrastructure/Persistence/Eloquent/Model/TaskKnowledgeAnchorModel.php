<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskKnowledgeAnchorModel extends Model
{
    use HasUuids;

    protected $table = 'task_knowledge_anchors';
    public $timestamps = false;

    protected $fillable = [
        'id', 'task_id', 'concept_id', 'concept_name', 'domain',
        'application_expectation', 'is_required', 'remediation_hint',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'task_id');
    }

    public function conceptTag(): BelongsTo
    {
        return $this->belongsTo(ConceptTagModel::class, 'concept_id');
    }
}
