<?php

namespace Src\Submission\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Str;
use Src\Submission\Domain\Submission\SubmissionPackageDetail;
use Src\Submission\Domain\Submission\SubmissionPackageRepository;
use Src\Submission\Domain\Submission\SubmissionPackageSummary;
use Src\Submission\Domain\Submission\SubmissionTrajectoryEntry;
use Src\Submission\Infrastructure\Persistence\Eloquent\Model\SubmissionPackageModel;

final class EloquentSubmissionPackageRepository implements SubmissionPackageRepository
{
    public function create(
        string $learnerSessionId,
        string $learnerId,
        string $taskId,
        string $scenarioId,
        ?string $sprintId,
        int $attemptNumber,
        ?string $layer1Text,
        ?array $layer2ArtifactIds,
        ?string $layer3Code,
        ?array $layer3ExecutionResult,
        ?array $layer4PlanningSnapshot,
        string $cacComplexityAtSub,
        string $cacAutonomyAtSub,
        string $cacContextAtSub,
        string $rankAtSubmission,
    ): string {
        // submitted_at is left unset — the migration's DEFAULT CURRENT_TIMESTAMP fills it in,
        // same convention as role_enrolments.enrolled_at and sprint_board_events.created_at.
        $model = SubmissionPackageModel::create([
            'id'                       => (string) Str::uuid(),
            'learner_session_id'       => $learnerSessionId,
            'learner_id'               => $learnerId,
            'task_id'                  => $taskId,
            'scenario_id'              => $scenarioId,
            'sprint_id'                => $sprintId,
            'attempt_number'           => $attemptNumber,
            'layer1_text'              => $layer1Text,
            'layer2_artifact_ids'      => $layer2ArtifactIds,
            'layer3_code'              => $layer3Code,
            'layer3_execution_result'  => $layer3ExecutionResult,
            'layer4_planning_snapshot' => $layer4PlanningSnapshot,
            'cac_complexity_at_sub'    => $cacComplexityAtSub,
            'cac_autonomy_at_sub'      => $cacAutonomyAtSub,
            'cac_context_at_sub'       => $cacContextAtSub,
            'rank_at_submission'       => $rankAtSubmission,
        ]);

        return $model->id;
    }

    public function countAttempts(string $learnerSessionId, string $taskId): int
    {
        return SubmissionPackageModel::where('learner_session_id', $learnerSessionId)
            ->where('task_id', $taskId)
            ->count();
    }

    public function findLatestId(string $learnerSessionId, string $taskId): ?string
    {
        return SubmissionPackageModel::where('learner_session_id', $learnerSessionId)
            ->where('task_id', $taskId)
            ->orderByDesc('attempt_number')
            ->value('id');
    }

    public function findById(string $id): ?SubmissionPackageSummary
    {
        $model = SubmissionPackageModel::find($id);
        if ($model === null) {
            return null;
        }

        return new SubmissionPackageSummary(
            id:            $model->id,
            taskId:        $model->task_id,
            attemptNumber: $model->attempt_number,
            submittedAt:   $model->submitted_at->format('Y-m-d H:i:s'),
        );
    }

    public function findDetailById(string $id): ?SubmissionPackageDetail
    {
        $model = SubmissionPackageModel::find($id);
        if ($model === null) {
            return null;
        }

        return new SubmissionPackageDetail(
            id:                     $model->id,
            learnerSessionId:       $model->learner_session_id,
            learnerId:              $model->learner_id,
            taskId:                 $model->task_id,
            scenarioId:             $model->scenario_id,
            sprintId:               $model->sprint_id,
            attemptNumber:          $model->attempt_number,
            layer1Text:             $model->layer1_text,
            layer2ArtifactIds:      $model->layer2_artifact_ids,
            layer3Code:             $model->layer3_code,
            layer4PlanningSnapshot: $model->layer4_planning_snapshot,
            cacComplexityAtSub:     $model->cac_complexity_at_sub->value,
            cacAutonomyAtSub:       $model->cac_autonomy_at_sub->value,
            cacContextAtSub:        $model->cac_context_at_sub->value,
            rankAtSubmission:       $model->rank_at_submission,
        );
    }

    public function findAllForLearner(string $learnerId): array
    {
        return SubmissionPackageModel::where('learner_id', $learnerId)
            ->orderBy('submitted_at')
            ->get()
            ->map(fn (SubmissionPackageModel $m) => new SubmissionTrajectoryEntry(
                id:                  $m->id,
                learnerSessionId:    $m->learner_session_id,
                taskId:              $m->task_id,
                attemptNumber:       $m->attempt_number,
                cacComplexityAtSub:  $m->cac_complexity_at_sub->value,
                cacAutonomyAtSub:    $m->cac_autonomy_at_sub->value,
                cacContextAtSub:     $m->cac_context_at_sub->value,
                rankAtSubmission:    $m->rank_at_submission,
                submittedAt:         $m->submitted_at->format('Y-m-d H:i:s'),
            ))
            ->all();
    }
}
