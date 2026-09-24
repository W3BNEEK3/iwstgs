<?php
namespace Src\EvalEngine\Application\Query\GetRecentDimensionTiers;

final class GetRecentDimensionTiersQuery
{
    public function __construct(
        public readonly string $learnerId,
        public readonly string $dimensionId,
        public readonly int $limit = 3,
    ) {}
}
