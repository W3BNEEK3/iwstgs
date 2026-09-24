<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository;

use Src\SimExecution\Domain\Session\LearnerSession;
use Src\SimExecution\Domain\Session\LearnerSessionRepository;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Mapper\LearnerSessionMapper;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerSessionModel;

final class EloquentLearnerSessionRepository implements LearnerSessionRepository
{
    public function __construct(private readonly LearnerSessionMapper $mapper) {}

    public function save(LearnerSession $session): void
    {
        $attributes = $this->mapper->toAttributes($session);
        $id = $attributes['id'];
        unset($attributes['id']);

        LearnerSessionModel::updateOrCreate(['id' => $id], $attributes);
    }

    public function findByLearnerAndProject(string $learnerId, string $projectId): ?LearnerSession
    {
        $model = LearnerSessionModel::where('learner_id', $learnerId)
            ->where('project_id', $projectId)
            ->latest('started_at')
            ->first();

        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function findById(string $id): ?LearnerSession
    {
        $model = LearnerSessionModel::find($id);
        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function findAllForLearner(string $learnerId): array
    {
        return LearnerSessionModel::where('learner_id', $learnerId)
            ->latest('started_at')
            ->get()
            ->map(fn (LearnerSessionModel $m) => $this->mapper->toEntity($m))
            ->all();
    }
}
