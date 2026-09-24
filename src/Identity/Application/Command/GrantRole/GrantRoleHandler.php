<?php
namespace Src\Identity\Application\Command\GrantRole;

use Src\Identity\Infrastructure\Persistence\Eloquent\Model\RoleModel;
use Src\Identity\Infrastructure\Persistence\Eloquent\Model\UserModel;

/**
 * Grants an RBAC role to a user. Idempotent — syncWithoutDetaching never
 * creates a duplicate pivot row, so this is safe to run more than once for
 * the same user (e.g. if an event is redelivered).
 *
 * No repository here: `roles`/`user_role` are pure RBAC plumbing with no
 * independent lifecycle of their own, the same reasoning that kept RankEvent
 * and DimensionScore as plain Eloquent writes in LearnerProfile's repository
 * rather than full aggregates.
 */
final class GrantRoleHandler
{
    public function handle(GrantRoleCommand $command): void
    {
        $role = RoleModel::where('name', $command->roleName)->first();
        if ($role === null) {
            return;
        }

        $user = UserModel::find($command->userId);
        $user?->roles()->syncWithoutDetaching([$role->id]);
    }
}
