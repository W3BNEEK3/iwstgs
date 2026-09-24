<?php
namespace Src\EvalEngine\Application\Query\GetRecentDimensionTiers;

use Src\EvalEngine\Domain\Evaluation\DimensionEvaluationRepository;

final class GetRecentDimensionTiersHandler
{
    public function __construct(private readonly DimensionEvaluationRepository $dimensionEvaluations) {}

    /** @return string[] most recent first */
    public function handle(GetRecentDimensionTiersQuery $query): array
    {
        return $this->dimensionEvaluations->findRecentTiersForDimension($query->learnerId, $query->dimensionId, $query->limit);
    }
}
