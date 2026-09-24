<?php
namespace Src\SimExecution\Domain\Session;

interface LearnerSessionRepository
{
    public function save(LearnerSession $session): void;

    public function findByLearnerAndProject(string $learnerId, string $projectId): ?LearnerSession;

    public function findById(string $id): ?LearnerSession;

    /** @return LearnerSession[] every session this learner has ever had, one per project, most recently started first */
    public function findAllForLearner(string $learnerId): array;
}
