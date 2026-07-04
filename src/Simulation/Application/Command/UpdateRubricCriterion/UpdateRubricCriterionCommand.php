<?php
namespace Src\Simulation\Application\Command\UpdateRubricCriterion;

final class UpdateRubricCriterionCommand
{
    public function __construct(
        public readonly string $criterionId,
        public readonly string $criterionText,
        public readonly string $weight,
        public readonly string $dimensionWeight,
    ) {}
}
