<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository;

use Src\SimExecution\Domain\Sprint\LearnerSprint;
use Src\SimExecution\Domain\Sprint\LearnerSprintRepository;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Mapper\LearnerSprintMapper;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerSprintModel;

final class EloquentLearnerSprintRepository implements LearnerSprintRepository
{
    public function __construct(private readonly LearnerSprintMapper $mapper) {}

    public function save(LearnerSprint $sprint): void
    {
        $attributes = $this->mapper->toAttributes($sprint);
        $id = $attributes['id'];
        unset($attributes['id']);

        LearnerSprintModel::updateOrCreate(['id' => $id], $attributes);
    }

    public function findById(string $id): ?LearnerSprint
    {
        $model = LearnerSprintModel::find($id);
        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function findLatestForSession(string $learnerSessionId): ?LearnerSprint
    {
        $model = LearnerSprintModel::where('learner_session_id', $learnerSessionId)
            ->orderByDesc('sprint_number')
            ->first();

        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function findAllForSession(string $learnerSessionId): array
    {
        return LearnerSprintModel::where('learner_session_id', $learnerSessionId)
            ->orderBy('sprint_number')
            ->get()
            ->map(fn (LearnerSprintModel $m) => $this->mapper->toEntity($m))
            ->all();
    }

    public function countForSession(string $learnerSessionId): int
    {
        return LearnerSprintModel::where('learner_session_id', $learnerSessionId)->count();
    }
}
