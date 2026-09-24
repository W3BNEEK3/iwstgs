<?php

namespace Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Str;
use Src\LearnerProfile\Domain\ConceptMastery\ConceptMasteryRepository;
use Src\LearnerProfile\Domain\ConceptMastery\ConceptMasterySummary;
use Src\LearnerProfile\Domain\ConceptMastery\MasteryStatus;
use Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Model\ConceptMasteryRecordModel;

final class EloquentConceptMasteryRepository implements ConceptMasteryRepository
{
    public function recordEncounter(string $learnerId, string $conceptId, bool $met): void
    {
        $model = ConceptMasteryRecordModel::where('learner_id', $learnerId)
            ->where('concept_id', $conceptId)
            ->first();

        $tasksEncountered = ($model->tasks_encountered ?? 0) + 1;
        $tasksMet = ($model->tasks_met ?? 0) + ($met ? 1 : 0);

        $status = match (true) {
            $tasksMet >= 2 => MasteryStatus::Mastered,
            $tasksMet >= 1 => MasteryStatus::PartiallyMet,
            default        => MasteryStatus::Encountered,
        };

        if ($model === null) {
            ConceptMasteryRecordModel::create([
                'id'                => (string) Str::uuid(),
                'learner_id'        => $learnerId,
                'concept_id'        => $conceptId,
                'status'            => $status,
                'tasks_encountered' => $tasksEncountered,
                'tasks_met'         => $tasksMet,
                'last_updated_at'   => now(),
            ]);
            return;
        }

        $model->update([
            'status'            => $status,
            'tasks_encountered' => $tasksEncountered,
            'tasks_met'         => $tasksMet,
            'last_updated_at'   => now(),
        ]);
    }

    public function findAllForLearner(string $learnerId): array
    {
        return ConceptMasteryRecordModel::where('learner_id', $learnerId)
            ->get()
            ->map(fn (ConceptMasteryRecordModel $m) => new ConceptMasterySummary(
                conceptId:         $m->concept_id,
                status:            $m->status->value,
                tasksEncountered:  $m->tasks_encountered,
                tasksMet:          $m->tasks_met,
            ))
            ->all();
    }
}
