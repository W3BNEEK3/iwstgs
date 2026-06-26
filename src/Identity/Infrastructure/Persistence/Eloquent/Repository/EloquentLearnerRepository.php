<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository;

use Src\Identity\Infrastructure\Persistence\Eloquent\Model\LearnerModel;
use Src\SimExecution\Domain\Enrollment\Learner;
use Src\SimExecution\Domain\Enrollment\LearnerId;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Mapper\LearnerMapper;

final class EloquentLearnerRepository implements LearnerRepository
{
    public function __construct(private readonly LearnerMapper $mapper) {}

    public function save(Learner $learner): void
    {
        $model = $this->mapper->toModel($learner);
        $model->save();
    }

    public function findByUserId(string $userId): ?Learner
    {
        $model = LearnerModel::where('user_id', $userId)->first();
        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function findById(LearnerId $id): ?Learner
    {
        $model = LearnerModel::find((string) $id);
        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function existsForUser(string $userId): bool
    {
        return LearnerModel::where('user_id', $userId)->exists();
    }
}