<?php
namespace Src\Simulation\Application\Command\CreateRubricCriterion;

final class CreateRubricCriterionCommand
{
    public function __construct(
        public readonly string  $taskId,
        public readonly string  $taskDimensionLabel,
        public readonly string  $parentDimensionId,
        public readonly string  $complexityLevel,
        public readonly string  $criterionText,
        public readonly string  $weight,
        public readonly string  $dimensionWeight,
        public readonly string  $claudeDetectionHint,
        public readonly string  $distinguishedDescription,
        public readonly string  $proficientDescription,
        public readonly string  $developingDescription,
        public readonly string  $beginningDescription,
        public readonly bool    $isArchitectural = false,
        public readonly bool    $isPlanningLayer = false,
        public readonly ?string $referenceDocAnchor = null,
    ) {}
}
