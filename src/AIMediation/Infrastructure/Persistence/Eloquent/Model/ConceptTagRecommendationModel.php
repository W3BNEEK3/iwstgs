<?php

namespace Src\AIMediation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Identity\Infrastructure\Persistence\Eloquent\Model\UserModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel;
use Src\Submission\Infrastructure\Persistence\Eloquent\Model\SubmissionPackageModel;

/**
 * Schema exists per the Implementation Plan, but nothing writes to this
 * table yet — the evaluation JSON schema (EvaluationPromptBuilder) doesn't
 * currently ask Claude for content-improvement suggestions, only grading.
 * Wiring that up is a distinct, disclosed follow-up, not silently skipped.
 */
class ConceptTagRecommendationModel extends Model
{
    use HasUuids;

    protected $table = 'concept_tag_recommendations';

    // created_at / resolved_at are the timestamp columns here — not the Eloquent pair.
    public $timestamps = false;

    protected $fillable = [
        'id', 'source_submission_id', 'task_id', 'recommendation_type',
        'proposed_content', 'status', 'curator_id', 'curator_notes',
        'created_at', 'resolved_at',
    ];

    protected $casts = [
        'proposed_content' => 'array',
        'created_at'       => 'datetime',
        'resolved_at'      => 'datetime',
    ];

    public function sourceSubmission(): BelongsTo
    {
        return $this->belongsTo(SubmissionPackageModel::class, 'source_submission_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'task_id');
    }

    public function curator(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'curator_id');
    }
}
