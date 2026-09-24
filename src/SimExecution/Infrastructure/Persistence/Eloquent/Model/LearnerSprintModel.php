<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\SimExecution\Domain\Sprint\SprintGoalSource;
use Src\SimExecution\Domain\Sprint\SprintStatus;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;

class LearnerSprintModel extends Model
{
    use HasUuids;

    protected $table = 'learner_sprints';

    // started_at / submitted_at are the timestamp columns here — not created_at/updated_at.
    public $timestamps = false;

    protected $fillable = [
        'id', 'learner_session_id', 'learner_id', 'project_id', 'sprint_number',
        'sprint_goal', 'sprint_goal_source', 'status', 'scope_warning_issued',
        'started_at', 'submitted_at',
    ];

    protected $casts = [
        'sprint_number'        => 'integer',
        'sprint_goal_source'   => SprintGoalSource::class,
        'status'               => SprintStatus::class,
        'scope_warning_issued' => 'boolean',
        'started_at'           => 'datetime',
        'submitted_at'         => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(LearnerSessionModel::class, 'learner_session_id');
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(LearnerModel::class, 'learner_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateModel::class, 'project_id');
    }
}
