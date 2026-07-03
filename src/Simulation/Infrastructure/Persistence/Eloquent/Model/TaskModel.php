<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\Simulation\Domain\Task\TaskType;

class TaskModel extends Model
{
    use HasUuids;

    protected $table = 'tasks';

    protected $fillable = [
        'id',
        'scenario_id',
        'sequence_order',
        'title',
        'task_brief',
        'domain',
        'task_type',
        'role_tags',
        'tools',
        'prerequisite_concepts',
        'is_cac_runtime_set',
        'fixed_complexity',
        'fixed_autonomy',
        'fixed_context_fidelity',
        'consequence_task_ids',
        'suggestion_task_ids',
        'is_architectural',
        'planning_layer_active',
        'code_execution_config',
        'model_response_summary',
        'time_limit_minutes',
        'is_published',
        'is_active',
    ];

    protected $casts = [
        'task_type'             => TaskType::class,
        'role_tags'             => 'array',
        'tools'                 => 'array',
        'prerequisite_concepts' => 'array',
        'consequence_task_ids'  => 'array',
        'suggestion_task_ids'   => 'array',
        'code_execution_config' => 'array',
        'sequence_order'        => 'integer',
        'time_limit_minutes'    => 'integer',
        'is_cac_runtime_set'    => 'boolean',
        'is_architectural'      => 'boolean',
        'planning_layer_active' => 'boolean',
        'is_published'          => 'boolean',
        'is_active'             => 'boolean',
    ];

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(ScenarioTemplateModel::class, 'scenario_id');
    }

    public function expectedDeliverables(): HasMany
    {
        return $this->hasMany(TaskExpectedDeliverableModel::class, 'task_id');
    }

    public function cacVariants(): HasMany
    {
        return $this->hasMany(TaskCacVariantModel::class, 'task_id');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(TaskDependencyModel::class, 'task_id');
    }

    public function knowledgeAnchors(): HasMany
    {
        return $this->hasMany(TaskKnowledgeAnchorModel::class, 'task_id');
    }

    public function guidancePrompts(): HasMany
    {
        return $this->hasMany(TaskGuidancePromptModel::class, 'task_id');
    }

    public function rubricCriteria(): HasMany
    {
        return $this->hasMany(RubricCriterionModel::class, 'task_id');
    }
}
