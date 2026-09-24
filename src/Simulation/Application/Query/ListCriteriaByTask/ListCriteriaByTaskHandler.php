<?php
namespace Src\Simulation\Application\Query\ListCriteriaByTask;

use Src\Simulation\Domain\Rubric\RubricCriterionRepository;

final class ListCriteriaByTaskHandler
{
    public function __construct(private readonly RubricCriterionRepository $repository) {}

    /** @return \Src\Simulation\Domain\Rubric\RubricCriterion[] */
    public function handle(ListCriteriaByTaskQuery $query): array
    {
        return $this->repository->findByTask($query->taskId);
    }
}
