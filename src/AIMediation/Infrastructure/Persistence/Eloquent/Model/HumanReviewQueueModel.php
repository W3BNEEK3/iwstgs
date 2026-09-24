<?php

namespace Src\AIMediation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\AIMediation\Domain\Review\ReviewerDecision;
use Src\AIMediation\Domain\Review\ReviewStatus;
use Src\Identity\Infrastructure\Persistence\Eloquent\Model\UserModel;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerModel;
use Src\Submission\Infrastructure\Persistence\Eloquent\Model\SubmissionPackageModel;

class HumanReviewQueueModel extends Model
{
    use HasUuids;

    protected $table = 'human_review_queue';

    // queued_at / resolved_at are the timestamp columns here — not created_at/updated_at.
    public $timestamps = false;

    protected $fillable = [
        'id', 'submission_id', 'evaluation_id', 'learner_id', 'status',
        'reviewer_id', 'reviewer_decision', 'reviewer_notes', 'queued_at', 'resolved_at',
    ];

    protected $casts = [
        'status'            => ReviewStatus::class,
        'reviewer_decision' => ReviewerDecision::class,
        'queued_at'         => 'datetime',
        'resolved_at'       => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(SubmissionPackageModel::class, 'submission_id');
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(EvaluationResultModel::class, 'evaluation_id');
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(LearnerModel::class, 'learner_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'reviewer_id');
    }
}
