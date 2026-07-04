<?php
namespace Src\Simulation\Application\Command\CreateRubricCriterion;

use Src\Simulation\Domain\Cac\CacLevel;
use Src\Simulation\Domain\Exceptions\RubricSetMissingException;
use Src\Simulation\Domain\Rubric\RubricCriterion;
use Src\Simulation\Domain\Rubric\RubricCriterionId;
use Src\Simulation\Domain\Rubric\RubricCriterionRepository;

final class CreateRubricCriterionHandler
{
    public function __construct(private readonly RubricCriterionRepository $repository) {}

    public function handle(CreateRubricCriterionCommand $command): void
    {
        $rubricSetId = $this->repository->rubricSetIdForTask($command->taskId);
        if ($rubricSetId === null) {
            throw new RubricSetMissingException($command->taskId);
        }

        $criterion = RubricCriterion::create(
            id: RubricCriterionId::generate(),
            rubricSetId: $rubricSetId,
            taskId: $command->taskId,
            taskDimensionLabel: $command->taskDimensionLabel,
            parentDimensionId: $command->parentDimensionId,
            complexityLevel: CacLevel::from($command->complexityLevel),
            criterionText: $command->criterionText,
            weight: $command->weight,
            dimensionWeight: $command->dimensionWeight,
            claudeDetectionHint: $command->claudeDetectionHint,
            distinguishedDescription: $command->distinguishedDescription,
            proficientDescription: $command->proficientDescription,
            developingDescription: $command->developingDescription,
            beginningDescription: $command->beginningDescription,
            isArchitectural: $command->isArchitectural,
            isPlanningLayer: $command->isPlanningLayer,
            referenceDocAnchor: $command->referenceDocAnchor,
        );

        $this->repository->save($criterion);
        
        foreach ($criterion->releaseEvents() as $event) {
            event($event);
        }
    }
}
