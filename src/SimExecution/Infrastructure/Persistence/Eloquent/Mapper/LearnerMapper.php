<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Mapper;

use Src\SimExecution\Domain\Enrollment\EntryCategory;
use Src\SimExecution\Domain\Enrollment\Learner;
use Src\SimExecution\Domain\Enrollment\LearnerId;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerModel;

final class LearnerMapper
{
    public function toEntity(LearnerModel $model): Learner
    {
        return Learner::reconstitute(
            id:              LearnerId::fromString($model->id),
            userId:          $model->user_id,
            entryCategory:   EntryCategory::from($model->entry_category),
            yearsExperience: $model->years_experience,
            organisationId:  $model->organisation_id,
        );
    }

    public function toModel(Learner $entity): LearnerModel
    {
        return new LearnerModel([
            'id'               => (string) $entity->learnerId(),
            'user_id'          => $entity->userId(),
            'organisation_id'  => $entity->organisationId(),
            'entry_category'   => $entity->entryCategory()->value,
            'years_experience' => $entity->yearsExperience(),
        ]);
    }
}