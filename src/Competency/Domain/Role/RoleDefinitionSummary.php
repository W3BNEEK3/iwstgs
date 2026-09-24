<?php
namespace Src\Competency\Domain\Role;

final class RoleDefinitionSummary
{
    /**
     * @param string[] $specializationTags
     * @param array<string, float> $dimensionWeights dimension_id => weight (0.0-1.0, sums to 1.0)
     * @param array<string, string> $dimensionThresholds dimension_id => minimum required DimensionTier value (basic|intermediate|advanced)
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly array $specializationTags,
        public readonly int $minYearsExperience,
        public readonly bool $isLeadRole,
        public readonly array $dimensionWeights = [],
        public readonly array $dimensionThresholds = [],
    ) {}
}
