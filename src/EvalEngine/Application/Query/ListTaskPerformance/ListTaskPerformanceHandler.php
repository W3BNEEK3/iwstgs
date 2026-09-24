<?php
namespace Src\EvalEngine\Application\Query\ListTaskPerformance;

use Src\EvalEngine\Domain\Evaluation\EvaluationResultRepository;
use Src\EvalEngine\Domain\Evaluation\TaskPerformanceSummary;

final class ListTaskPerformanceHandler
{
    public function __construct(private readonly EvaluationResultRepository $evaluationResults) {}

    /** @return TaskPerformanceSummary[] */
    public function handle(ListTaskPerformanceQuery $query): array
    {
        return $this->evaluationResults->findPerformanceByTask();
    }
}
