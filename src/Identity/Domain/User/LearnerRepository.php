<?php
namespace Src\Identity\Domain\User;

interface LearnerRepository 
{
    public function save(Learner $learner): void
    
    public function findByEmail(string $email): ?Learner
    
    public function findById(LearnerId $id): ?Learner
    
    public function existByEmail(string $email): bool
}