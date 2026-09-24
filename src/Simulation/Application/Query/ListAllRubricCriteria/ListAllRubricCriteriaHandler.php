<?php
namespace Src\Simulation\Application\Query\ListAllRubricCriteria;

use Src\Simulation\Domain\Rubric\RubricCriterionRepository;

final class ListAllRubricCriteriaHandler
{
    public function __construct(private readonly RubricCriterionRepository $repository) {}

    public function handle(ListAllRubricCriteriaQuery $query): array
    {
        return $this->repository->findAllWithTaskTitle();
    }
}
