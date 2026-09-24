<?php
namespace Src\Simulation\Application\Command\RemoveRubricCriterion;

use Src\Simulation\Domain\Rubric\RubricCriterionId;
use Src\Simulation\Domain\Rubric\RubricCriterionRepository;

final class RemoveRubricCriterionHandler
{
    public function __construct(private readonly RubricCriterionRepository $repository) {}

    public function handle(RemoveRubricCriterionCommand $command): void
    {
        $this->repository->remove(RubricCriterionId::fromString($command->criterionId));
    }
}
