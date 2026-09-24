<?php

namespace Src\EvalEngine\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\EvalEngine\Domain\Evaluation\GapType;
use Src\EvalEngine\Domain\Evaluation\OverallTier;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerModel;
use Src\Submission\Infrastructure\Persistence\Eloquent\Model\SubmissionPackageModel;

class EvaluationResultModel extends Model
{
    use HasUuids;

    protected $table = 'evaluation_results';

    // evaluated_at is the only timestamp column — not created_at/updated_at.
    public $timestamps = false;

    protected $fillable = [
        'id', 'submission_id', 'learner_id', 'overall_tier', 'passes_threshold',
        'gap_type', 'is_uncertain', 'follow_up_prompt_id', 'follow_up_prompt_text',
        'follow_up_status', 'evaluated_at',
    ];

    protected $casts = [
        'overall_tier'     => OverallTier::class,
        'passes_threshold' => 'boolean',
        'gap_type'         => GapType::class,
        'is_uncertain'     => 'boolean',
        'evaluated_at'     => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(SubmissionPackageModel::class, 'submission_id');
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(LearnerModel::class, 'learner_id');
    }

    public function followUpPrompt(): BelongsTo
    {
        return $this->belongsTo(FollowUpPromptTemplateModel::class, 'follow_up_prompt_id');
    }

    public function dimensions(): HasMany
    {
        return $this->hasMany(DimensionEvaluationModel::class, 'evaluation_id');
    }
}
