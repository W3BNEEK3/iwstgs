<?php
namespace Src\SimExecution\Application\Query\GetProjectDetail;

final class ProjectDetailView
{
    /** @param RoleOption[] $roleOptions */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $tagline,
        public readonly string $businessContext,
        public readonly string $difficultyLevel,
        public readonly array $roleOptions,
        public readonly bool $isEnrolled,
        public readonly ?string $sessionStatus,
    ) {}
}
