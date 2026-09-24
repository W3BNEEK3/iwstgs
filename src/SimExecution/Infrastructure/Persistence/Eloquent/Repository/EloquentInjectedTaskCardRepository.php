<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Str;
use Src\SimExecution\Domain\Board\InjectedTaskCardRepository;
use Src\SimExecution\Domain\Board\InjectedTaskCardSummary;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\InjectedTaskCardModel;

final class EloquentInjectedTaskCardRepository implements InjectedTaskCardRepository
{
    public function create(
        string $learnerSessionId,
        string $learnerId,
        string $cardType,
        string $sourceTaskId,
        ?string $injectedTaskId,
        ?array $generatedTaskContent,
        ?string $targetSprintId,
        string $visualTreatment,
        ?string $suggestionType,
    ): string {
        // injected_at is left unset — the migration's DEFAULT CURRENT_TIMESTAMP fills it in.
        $model = InjectedTaskCardModel::create([
            'id'                      => (string) Str::uuid(),
            'learner_session_id'      => $learnerSessionId,
            'learner_id'              => $learnerId,
            'card_type'               => $cardType,
            'source_task_id'          => $sourceTaskId,
            'injected_task_id'        => $injectedTaskId,
            'generated_task_content'  => $generatedTaskContent,
            'target_sprint_id'        => $targetSprintId,
            'visual_treatment'        => $visualTreatment,
            'status'                  => 'pending',
            'suggestion_type'         => $suggestionType,
        ]);

        return $model->id;
    }

    public function findById(string $id): ?InjectedTaskCardSummary
    {
        $model = InjectedTaskCardModel::find($id);
        return $model ? $this->toSummary($model) : null;
    }

    public function findAllForSession(string $learnerSessionId): array
    {
        return InjectedTaskCardModel::where('learner_session_id', $learnerSessionId)
            ->get()
            ->map(fn (InjectedTaskCardModel $m) => $this->toSummary($m))
            ->all();
    }

    public function findLatestId(string $learnerSessionId, string $sourceTaskId): ?string
    {
        return InjectedTaskCardModel::where('learner_session_id', $learnerSessionId)
            ->where('source_task_id', $sourceTaskId)
            ->orderByDesc('injected_at')
            ->value('id');
    }

    private function toSummary(InjectedTaskCardModel $m): InjectedTaskCardSummary
    {
        return new InjectedTaskCardSummary(
            id:               $m->id,
            learnerSessionId: $m->learner_session_id,
            learnerId:        $m->learner_id,
            cardType:         $m->card_type->value,
            sourceTaskId:     $m->source_task_id,
            injectedTaskId:   $m->injected_task_id,
            targetSprintId:   $m->target_sprint_id,
            visualTreatment:  $m->visual_treatment->value,
            status:           $m->status->value,
            suggestionType:   $m->suggestion_type?->value,
            generatedTaskContent: $m->generated_task_content,
        );
    }
}
