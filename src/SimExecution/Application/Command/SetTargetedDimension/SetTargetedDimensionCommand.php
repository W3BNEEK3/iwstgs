<?php

namespace Src\SimExecution\Application\Command\SetTargetedDimension;

/**
 * BLD §11.4 — Records on the learner session which competence dimension
 * should be prioritised in the next scenario selection. Dispatched by
 * ScenarioTransitionService when HabitFlag evidence confirms a persistent
 * weakness across the just-completed scenario.
 */
final class SetTargetedDimensionCommand
{
    public function __construct(
        public readonly string $learnerSessionId,
        public readonly string $dimensionId,
    ) {}
}
