<?php
namespace Src\SimExecution\Application\Command\EnrolInProject;

final class EnrolInProjectCommand
{
    /**
     * @param string|null $roleId required for classic and Work Experience projects; Build projects
     *   (you are the whole team) enrol into the Solo Developer role automatically
     * @param string|null $stackVariantId required for Build projects
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $projectId,
        public readonly ?string $roleId,
        public readonly ?string $stackVariantId = null,
    ) {}
}
