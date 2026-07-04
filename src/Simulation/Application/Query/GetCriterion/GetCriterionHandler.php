<?php
namespace Src\Simulation\Application\Query\GetCriterion;

use Src\Simulation\Domain\Rubric\RubricCriterion;
use Src\Simulation\Domain\Rubric\RubricCriterionId;
use Src\Simulation\Domain\Rubric\RubricCriterionRepository;

final class GetCriterionHandler
{
    public function __construct(private readonly RubricCriterionRepository $repository) {}

    public function handle(GetCriterionQuery $query): ?RubricCriterion
    {
        return $this->repository->findById(RubricCriterionId::fromString($query->criterionId));
    }
}
