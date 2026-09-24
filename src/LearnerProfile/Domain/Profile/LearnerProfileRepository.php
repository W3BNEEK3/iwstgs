<?php

namespace Src\LearnerProfile\Domain\Profile;

interface LearnerProfileRepository
{
    public function save(LearnerProfile $profile): void;

    public function findByLearnerId(string $learnerId): ?LearnerProfile;

    public function findById(LearnerProfileId $id): ?LearnerProfile;

    public function existsForLearner(string $learnerId): bool;
}
