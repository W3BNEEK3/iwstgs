<?php
namespace Src\Simulation\Application\Command\UpdateRubricCriterion;

use Src\Simulation\Domain\Rubric\RubricCriterionId;
use Src\Simulation\Domain\Rubric\RubricCriterionRepository;

final class UpdateRubricCriterionHandler
{
    public function __construct(private readonly RubricCriterionRepository $repository) {}

    public function handle(UpdateRubricCriterionCommand $command): void
    {
        $criterion = $this->repository->findById(RubricCriterionId::fromString($command->criterionId));
        if ($criterion === null) {
            throw new \DomainException("Criterion not found: {$command->criterionId}");
        }

        $criterion->updateText($command->criterionText);
        $criterion->reweight($command->weight, $command->dimensionWeight);

        $this->repository->save($criterion);
    }
}
