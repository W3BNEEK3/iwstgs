<?php
namespace Src\SimExecution\Domain\RoleEnrolment;

/**
 * role_enrolments has no domain aggregate — it's write-once, never updated,
 * never independently mutated (same reasoning as RankEvent/DimensionScore in
 * LearnerProfile, and RubricSet in Simulation). This repository exists only
 * to create the row inside EnrolInProjectHandler's transaction.
 */
interface RoleEnrolmentRepository
{
    /** @return string the new role_enrolments.id */
    public function create(string $learnerId, string $roleId, string $projectId): string;
}
