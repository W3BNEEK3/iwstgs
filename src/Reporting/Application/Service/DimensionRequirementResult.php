<?php
namespace Src\Reporting\Application\Service;

final class DimensionRequirementResult
{
    public function __construct(
        public readonly string $dimensionId,
        public readonly string $requiredTier,
        public readonly string $achievedTier,
    ) {}
}
