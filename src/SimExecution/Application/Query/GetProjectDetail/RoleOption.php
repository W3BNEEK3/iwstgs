<?php
namespace Src\SimExecution\Application\Query\GetProjectDetail;

final class RoleOption
{
    public function __construct(
        public readonly string $roleId,
        public readonly string $title,
        public readonly int $minYearsExperience,
        public readonly bool $isLeadRole,
        public readonly bool $isEligible,
    ) {}
}
