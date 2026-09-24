<?php

namespace Src\EvalEngine\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Str;
use Src\EvalEngine\Domain\Gap\GapFlagRepository;
use Src\EvalEngine\Domain\Gap\GapFlagSummary;
use Src\EvalEngine\Infrastructure\Persistence\Eloquent\Model\GapFlagModel;

final class EloquentGapFlagRepository implements GapFlagRepository
{
    public function create(
        string $learnerId,
        string $dimensionId,
        ?string $subCriterionId,
        string $sourceTaskId,
        string $sourceSessionId,
    ): string {
        $model = GapFlagModel::create([
            'id'                => (string) Str::uuid(),
            'learner_id'        => $learnerId,
            'dimension_id'      => $dimensionId,
            'sub_criterion_id'  => $subCriterionId,
            'source_task_id'    => $sourceTaskId,
            'source_session_id' => $sourceSessionId,
        ]);

        return $model->id;
    }

    public function resolveOpenForDimension(string $learnerId, string $dimensionId): void
    {
        GapFlagModel::where('learner_id', $learnerId)
            ->where('dimension_id', $dimensionId)
            ->where('is_resolved', false)
            ->update(['is_resolved' => true, 'resolved_at' => now()]);
    }

    public function findAllForLearner(string $learnerId): array
    {
        return GapFlagModel::where('learner_id', $learnerId)
            ->orderBy('created_at')
            ->get()
            ->map(fn (GapFlagModel $m) => new GapFlagSummary(
                id:               $m->id,
                dimensionId:      $m->dimension_id,
                subCriterionId:   $m->sub_criterion_id,
                sourceTaskId:     $m->source_task_id,
                sourceSessionId:  $m->source_session_id,
                isResolved:       $m->is_resolved,
                resolvedAt:       $m->resolved_at?->toDateTimeString(),
                createdAt:        $m->created_at?->toDateTimeString(),
            ))
            ->all();
    }
}
