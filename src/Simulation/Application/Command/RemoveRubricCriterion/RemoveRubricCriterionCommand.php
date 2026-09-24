<?php
namespace Src\Simulation\Application\Command\RemoveRubricCriterion;

final class RemoveRubricCriterionCommand
{
    public function __construct(public readonly string $criterionId) {}
}
