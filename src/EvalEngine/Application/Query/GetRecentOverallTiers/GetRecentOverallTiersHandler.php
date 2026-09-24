<?php
namespace Src\EvalEngine\Application\Query\GetRecentOverallTiers;

use Src\EvalEngine\Domain\Evaluation\EvaluationResultRepository;

final class GetRecentOverallTiersHandler
{
    public function __construct(private readonly EvaluationResultRepository $evaluationResults) {}

    /** @return string[] most recent first */
    public function handle(GetRecentOverallTiersQuery $query): array
    {
        return $this->evaluationResults->findRecentOverallTiers($query->learnerId, $query->limit);
    }
}
