<?php

namespace Src\EvalEngine\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Str;
use Src\EvalEngine\Domain\Evaluation\DimensionEvaluationSummary;
use Src\EvalEngine\Domain\Evaluation\EvaluationResultRepository;
use Src\EvalEngine\Domain\Evaluation\EvaluationResultSummary;
use Src\EvalEngine\Domain\Evaluation\TaskPerformanceSummary;
use Src\EvalEngine\Infrastructure\Persistence\Eloquent\Model\DimensionEvaluationModel;
use Src\EvalEngine\Infrastructure\Persistence\Eloquent\Model\EvaluationResultModel;

final class EloquentEvaluationResultRepository implements EvaluationResultRepository
{
    public function create(
        string $submissionId,
        string $learnerId,
        string $overallTier,
        bool $passesThreshold,
        ?string $gapType,
        bool $isUncertain,
        ?string $followUpPromptId,
    ): string {
        // evaluated_at is left unset — the migration's DEFAULT CURRENT_TIMESTAMP fills it in.
        $model = EvaluationResultModel::create([
            'id'                  => (string) Str::uuid(),
            'submission_id'       => $submissionId,
            'learner_id'          => $learnerId,
            'overall_tier'        => $overallTier,
            'passes_threshold'    => $passesThreshold,
            'gap_type'            => $gapType,
            'is_uncertain'        => $isUncertain,
            'follow_up_prompt_id' => $followUpPromptId,
        ]);

        return $model->id;
    }

    public function findBySubmissionId(string $submissionId): ?EvaluationResultSummary
    {
        $model = EvaluationResultModel::with('dimensions')->where('submission_id', $submissionId)->first();
        if ($model === null) {
            return null;
        }

        $dimensions = $model->dimensions->map(fn (DimensionEvaluationModel $d) => new DimensionEvaluationSummary(
            dimensionId:        $d->dimension_id,
            taskDimensionLabel: $d->task_dimension_label,
            tierAchieved:       $d->tier_achieved->value,
            criteriaMet:        $d->criteria_met,
            criteriaMissed:     $d->criteria_missed,
            evaluatorNotes:     $d->evaluator_notes,
        ))->all();

        return new EvaluationResultSummary(
            id:               $model->id,
            submissionId:     $model->submission_id,
            learnerId:        $model->learner_id,
            overallTier:      $model->overall_tier->value,
            passesThreshold:  $model->passes_threshold,
            gapType:          $model->gap_type?->value,
            isUncertain:      $model->is_uncertain,
            followUpPromptId: $model->follow_up_prompt_id,
            dimensions:       $dimensions,
        );
    }

    public function findRecentOverallTiers(string $learnerId, int $limit): array
    {
        return EvaluationResultModel::where('learner_id', $learnerId)
            ->orderByDesc('evaluated_at')
            ->limit($limit)
            ->get()
            ->map(fn (EvaluationResultModel $m) => $m->overall_tier->value)
            ->all();
    }

    public function findAllDimensionTiersForSession(string $learnerSessionId): array
    {
        return DimensionEvaluationModel::whereHas(
            'evaluation.submission',
            fn ($q) => $q->where('learner_session_id', $learnerSessionId),
        )
            ->get()
            ->map(fn (DimensionEvaluationModel $d) => $d->tier_achieved->value)
            ->all();
    }

    public function findPerformanceByTask(): array
    {
        return EvaluationResultModel::with('submission')
            ->get()
            ->filter(fn (EvaluationResultModel $m) => $m->submission !== null)
            ->groupBy(fn (EvaluationResultModel $m) => $m->submission->task_id)
            ->map(function ($group, $taskId) {
                $tierCounts = [];
                foreach ($group as $m) {
                    $tier = $m->overall_tier->value;
                    $tierCounts[$tier] = ($tierCounts[$tier] ?? 0) + 1;
                }

                return new TaskPerformanceSummary(
                    taskId:        $taskId,
                    attemptCount:  $group->count(),
                    passCount:     $group->where('passes_threshold', true)->count(),
                    tierCounts:    $tierCounts,
                );
            })
            ->values()
            ->all();
    }

    public function writeFollowUpPrompt(string $evaluationId, string $followUpPromptText, string $status): void
    {
        $changes = ['follow_up_status' => $status];
        if ($followUpPromptText !== '') {
            $changes['follow_up_prompt_text'] = $followUpPromptText;
        }

        EvaluationResultModel::whereKey($evaluationId)->update($changes);
    }
}
