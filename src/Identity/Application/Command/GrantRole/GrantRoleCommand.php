<?php
namespace Src\Identity\Application\Command\GrantRole;

final class GrantRoleCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $roleName,
    ) {}
}
