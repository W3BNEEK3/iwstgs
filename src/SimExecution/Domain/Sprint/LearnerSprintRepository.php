<?php
namespace Src\SimExecution\Domain\Sprint;

interface LearnerSprintRepository
{
    public function save(LearnerSprint $sprint): void;

    public function findById(string $id): ?LearnerSprint;

    /** The most recent sprint for a session — planning/active is "current"; submitted/evaluated means it's history. */
    public function findLatestForSession(string $learnerSessionId): ?LearnerSprint;

    /** @return LearnerSprint[] */
    public function findAllForSession(string $learnerSessionId): array;

    public function countForSession(string $learnerSessionId): int;
}
