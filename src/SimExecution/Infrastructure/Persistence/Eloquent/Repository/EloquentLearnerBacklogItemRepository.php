<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository;

use Src\SimExecution\Domain\Backlog\LearnerBacklogItem;
use Src\SimExecution\Domain\Backlog\LearnerBacklogItemRepository;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Mapper\LearnerBacklogItemMapper;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerBacklogItemModel;

final class EloquentLearnerBacklogItemRepository implements LearnerBacklogItemRepository
{
    public function __construct(private readonly LearnerBacklogItemMapper $mapper) {}

    public function save(LearnerBacklogItem $item): void
    {
        $attributes = $this->mapper->toAttributes($item);
        $id = $attributes['id'];
        unset($attributes['id']);

        LearnerBacklogItemModel::updateOrCreate(['id' => $id], $attributes);
    }

    public function findById(string $id): ?LearnerBacklogItem
    {
        $model = LearnerBacklogItemModel::find($id);
        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function findAllForSession(string $learnerSessionId): array
    {
        return LearnerBacklogItemModel::where('learner_session_id', $learnerSessionId)
            ->get()
            ->map(fn (LearnerBacklogItemModel $m) => $this->mapper->toEntity($m))
            ->all();
    }

    public function findAllForSprint(string $sprintId): array
    {
        return LearnerBacklogItemModel::where('sprint_id', $sprintId)
            ->get()
            ->map(fn (LearnerBacklogItemModel $m) => $this->mapper->toEntity($m))
            ->all();
    }

    public function existsForSession(string $learnerSessionId): bool
    {
        return LearnerBacklogItemModel::where('learner_session_id', $learnerSessionId)->exists();
    }

    public function seededTemplateIdsForSession(string $learnerSessionId): array
    {
        return LearnerBacklogItemModel::where('learner_session_id', $learnerSessionId)
            ->whereNotNull('template_item_id')
            ->pluck('template_item_id')
            ->all();
    }
}
