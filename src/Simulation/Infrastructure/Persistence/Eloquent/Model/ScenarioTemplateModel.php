<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScenarioTemplateModel extends Model
{
    use HasUuids;

    protected $table = 'scenario_templates';

    protected $fillable = [
        'id',
        'project_id',
        'sequence_order',
        'title',
        'narrative_context',
        'situation_trigger',
        'situation_trigger_type',
        'learner_role_label',
        'default_autonomy_level',
        'is_diagnostic',
        'is_published',
        'is_active',
    ];

    protected $casts = [
        'sequence_order' => 'integer',
        'is_diagnostic'  => 'boolean',
        'is_published'   => 'boolean',
        'is_active'      => 'boolean',
        'situation_trigger_type' => \Src\Simulation\Domain\Scenario\SituationTriggerType::class,
        'default_autonomy_level' => \Src\Simulation\Domain\Cac\CacLevel::class,

    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateModel::class, 'project_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(TaskModel::class, 'scenario_id');
    }

    public function referenceMaterials(): HasMany
    {
        return $this->hasMany(ScenarioReferenceMaterialModel::class, 'scenario_id');
    }
}
