<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Str;
use Src\SimExecution\Domain\RoleEnrolment\RoleEnrolmentRepository;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\RoleEnrolmentModel;

final class EloquentRoleEnrolmentRepository implements RoleEnrolmentRepository
{
    public function create(string $learnerId, string $roleId, string $projectId): string
    {
        // enrolled_at is left unset — the migration's DEFAULT CURRENT_TIMESTAMP fills it in,
        // same convention as learner_profiles.updated_at and rank_events.created_at.
        $model = RoleEnrolmentModel::create([
            'id'          => (string) Str::uuid(),
            'learner_id'  => $learnerId,
            'role_id'     => $roleId,
            'project_id'  => $projectId,
            'is_gated'    => false,
        ]);

        return $model->id;
    }
}
