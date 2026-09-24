<?php
namespace Src\SimExecution\Application\Command\EnrolInProject;

final class EnrolInProjectCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $projectId,
        public readonly string $roleId,
    ) {}
}
