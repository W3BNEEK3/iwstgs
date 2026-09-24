<?php
namespace Src\EvalEngine\Application\Query\GetRecentOverallTiers;

final class GetRecentOverallTiersQuery
{
    public function __construct(
        public readonly string $learnerId,
        public readonly int $limit = 3,
    ) {}
}
