<?php
namespace Src\Simulation\Domain\Rubric;

use Src\Shared\Domain\DomainEvent;

final class RubricCriterionCreated extends DomainEvent
{
    public function __construct(
        public readonly string $criterionId,
        public readonly string $taskId,
        public readonly string $parentDimensionId,
    ) {
        parent::__construct();
    }
}
