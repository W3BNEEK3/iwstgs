<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Src\Organizations\Infrastructure\Persistence\Eloquent\Model\OrganizationModel;
use Src\Simulation\Domain\Project\DifficultyLevel;

class ProjectTemplateModel extends Model
{
    use HasUuids;

    protected $table = 'project_templates';

    protected $fillable = [
        'id',
        'title',
        'tagline',
        'project_type',
        'business_domain',
        'business_context',
        'stakeholders',
        'overarching_constraints',
        'tech_context',
        'specialization_tags',
        'organisation_id',
        'rubric_set_id',
        'coding_guidelines',
        'velocity_estimate',
        'difficulty_level',
        'is_published',
        'is_active',
    ];

    protected $casts = [
        'stakeholders'            => 'array',
        'overarching_constraints' => 'array',
        'tech_context'            => 'array',
        'specialization_tags'     => 'array',
        'velocity_estimate'       => 'array',
        'difficulty_level'        => DifficultyLevel::class,
        'is_published'            => 'boolean',
        'is_active'               => 'boolean',
    ];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organisation_id');
    }

    public function scenarios(): HasMany
    {
        return $this->hasMany(ScenarioTemplateModel::class, 'project_id');
    }

    public function rubricSet(): HasOne
    {
        return $this->hasOne(RubricSetModel::class, 'project_id');
    }

    public function vaultItems(): HasMany
    {
        return $this->hasMany(ArtifactVaultItemModel::class, 'project_id');
    }

    public function backlogItems(): HasMany
    {
        return $this->hasMany(BacklogItemTemplateModel::class, 'project_id');
    }
}
