<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Competency\Infrastructure\Persistence\Eloquent\Model\RoleDefinitionModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;

class RoleEnrolmentModel extends Model
{
    use HasUuids;

    protected $table = 'role_enrolments';

    // enrolled_at is the only timestamp column — not Eloquent's created_at/updated_at pair.
    public $timestamps = false;

    protected $fillable = [
        'id', 'learner_id', 'role_id', 'project_id', 'is_gated', 'enrolled_at',
    ];

    protected $casts = [
        'is_gated'    => 'boolean',
        'enrolled_at' => 'datetime',
    ];

    public function learner(): BelongsTo
    {
        return $this->belongsTo(LearnerModel::class, 'learner_id');
    }

    // One-way into Competency — role_definitions is a definitions table we read, not own.
    public function role(): BelongsTo
    {
        return $this->belongsTo(RoleDefinitionModel::class, 'role_id');
    }

    // One-way into Simulation, same rule as every other runtime table referencing a blueprint.
    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateModel::class, 'project_id');
    }
}
