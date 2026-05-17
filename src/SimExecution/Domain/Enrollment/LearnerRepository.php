<?php
namespace Src\SimExecution\Domain\Enrollment;

interface LearnerRepository
{
    public function save(Learner $learner): void;
    
    public function findByUserId(string $userId): ?Learner;

    public function findById(LearnerId $id): ?learner;

    public function existsForUser(string $userId): bool;
}