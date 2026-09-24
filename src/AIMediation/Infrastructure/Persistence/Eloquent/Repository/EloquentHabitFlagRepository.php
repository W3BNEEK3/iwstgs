<?php

namespace Src\AIMediation\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\AIMediation\Domain\Habit\HabitFlagRepository;
use Src\AIMediation\Domain\Habit\HabitFlagSummary;
use Src\AIMediation\Infrastructure\Persistence\Eloquent\Model\HabitFlagModel;

final class EloquentHabitFlagRepository implements HabitFlagRepository
{
    public function recordObservation(string $learnerId, string $habitDescription, ?string $sourceTaskId): string
    {
        $model = HabitFlagModel::where('learner_id', $learnerId)
            ->where('habit_description', $habitDescription)
            ->where('is_resolved', false)
            ->first();

        if ($model === null) {
            $model = HabitFlagModel::create([
                'id'                => (string) Str::uuid(),
                'learner_id'        => $learnerId,
                'habit_description' => $habitDescription,
                'source_task_id'    => $sourceTaskId,
                'observation_count' => 1,
            ]);

            return $model->id;
        }

        $model->update([
            'observation_count' => $model->observation_count + 1,
            'source_task_id'    => $sourceTaskId ?? $model->source_task_id,
        ]);

        return $model->id;
    }

    public function findQueuedForSuggestion(string $learnerId): array
    {
        return HabitFlagModel::where('learner_id', $learnerId)
            ->where('is_resolved', false)
            ->whereNull('suggestion_task_id')
            ->get()
            ->map(fn (HabitFlagModel $m) => new HabitFlagSummary(
                id:                $m->id,
                learnerId:         $m->learner_id,
                habitDescription:  $m->habit_description,
                sourceTaskId:      $m->source_task_id,
                observationCount:  $m->observation_count,
                suggestionCardId:  $m->suggestion_task_id,
                isResolved:        $m->is_resolved,
            ))
            ->all();
    }

    public function attachSuggestionCard(string $habitFlagId, string $injectedCardId): void
    {
        HabitFlagModel::where('id', $habitFlagId)->update(['suggestion_task_id' => $injectedCardId]);
    }

    /**
     * Gap 1 / BLD §11.4 — Queries gap_flags (which carry dimension_id and
     * source_session_id) grouped by dimension to find which dimension had the
     * most unresolved failures in this session. Returns the dimension_id
     * string or null if no gap flags exist for this session.
     *
     * Uses gap_flags rather than habit_flags because gap_flags have explicit
     * dimension_id FK linkage; habit_flags store free-text habit_description
     * and have no direct dimension foreign key.
     */
    public function findDominantWeaknessDimension(string $learnerId, string $sessionId): ?string
    {
        $row = DB::table('gap_flags')
            ->where('learner_id', $learnerId)
            ->where('source_session_id', $sessionId)
            ->where('is_resolved', false)
            ->selectRaw('dimension_id, COUNT(*) as cnt')
            ->groupBy('dimension_id')
            ->orderByDesc('cnt')
            ->first();

        return $row?->dimension_id;
    }
}
