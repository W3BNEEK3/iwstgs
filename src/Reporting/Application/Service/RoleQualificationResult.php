<?php
namespace Src\Reporting\Application\Service;

final class RoleQualificationResult
{
    /**
     * @param DimensionRequirementResult[] $met
     * @param DimensionRequirementResult[] $unmet
     */
    public function __construct(
        public readonly string $roleId,
        public readonly string $roleTitle,
        public readonly bool $isQualified,
        public readonly array $met,
        public readonly array $unmet,
    ) {}
}
