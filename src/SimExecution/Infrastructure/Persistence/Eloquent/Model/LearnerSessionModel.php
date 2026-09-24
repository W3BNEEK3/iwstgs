<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\SimExecution\Domain\Session\SessionStatus;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel;

class LearnerSessionModel extends Model
{
    use HasUuids;

    protected $table = 'learner_sessions';

    // started_at / completed_at are the timestamp columns here — not created_at/updated_at.
    public $timestamps = false;

    protected $fillable = [
        'id', 'learner_id', 'project_id', 'role_enrolment_id', 'status',
        'current_scenario_id', 'current_task_id', 'current_sprint_id',
        'induction_completed_at', 'started_at', 'completed_at',
        'targeted_dimension_id',
    ];

    protected $casts = [
        'status'                  => SessionStatus::class,
        'induction_completed_at'  => 'datetime',
        'started_at'              => 'datetime',
        'completed_at'            => 'datetime',
    ];

    public function learner(): BelongsTo
    {
        return $this->belongsTo(LearnerModel::class, 'learner_id');
    }

    public function roleEnrolment(): BelongsTo
    {
        return $this->belongsTo(RoleEnrolmentModel::class, 'role_enrolment_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateModel::class, 'project_id');
    }

    public function currentScenario(): BelongsTo
    {
        return $this->belongsTo(ScenarioTemplateModel::class, 'current_scenario_id');
    }

    public function currentTask(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'current_task_id');
    }

    // current_sprint_id stays a plain attribute, no relationship, until Phase 6 creates
    // learner_sprints — this is 5a Reconciliation #4, carried through unchanged.
}
