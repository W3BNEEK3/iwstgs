<?php

namespace Src\Submission\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerModel;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerSessionModel;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerSprintModel;
use Src\Submission\Domain\Submission\CacLevelAtSubmission;

class SubmissionPackageModel extends Model
{
    use HasUuids;

    protected $table = 'submission_packages';

    // submitted_at is the only timestamp column — not created_at/updated_at.
    public $timestamps = false;

    protected $fillable = [
        'id', 'learner_session_id', 'learner_id', 'task_id', 'scenario_id', 'sprint_id',
        'attempt_number', 'layer1_text', 'layer2_artifact_ids', 'layer3_code',
        'layer3_execution_result', 'layer4_planning_snapshot',
        'cac_complexity_at_sub', 'cac_autonomy_at_sub', 'cac_context_at_sub',
        'rank_at_submission', 'submitted_at',
    ];

    protected $casts = [
        'attempt_number'           => 'integer',
        'layer2_artifact_ids'      => 'array',
        'layer3_execution_result'  => 'array',
        'layer4_planning_snapshot' => 'array',
        'cac_complexity_at_sub'    => CacLevelAtSubmission::class,
        'cac_autonomy_at_sub'      => CacLevelAtSubmission::class,
        'cac_context_at_sub'       => CacLevelAtSubmission::class,
        'submitted_at'             => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(LearnerSessionModel::class, 'learner_session_id');
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(LearnerModel::class, 'learner_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'task_id');
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(ScenarioTemplateModel::class, 'scenario_id');
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(LearnerSprintModel::class, 'sprint_id');
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(SubmissionArtifactModel::class, 'submission_id');
    }
}
