<?php

namespace Src\SimExecution\Application\Command\SetTargetedDimension;

use Src\SimExecution\Domain\Session\LearnerSessionRepository;

/**
 * BLD §11.4 — Sets targeted_dimension_id on the learner session so that the
 * next scenario onboarding can surface a "This scenario focuses on [X]"
 * notice. Dispatched by ScenarioTransitionService after it detects persistent
 * HabitFlag evidence for a specific dimension across the just-completed
 * scenario.
 */
final class SetTargetedDimensionHandler
{
    public function __construct(
        private readonly LearnerSessionRepository $sessions,
    ) {}

    public function handle(SetTargetedDimensionCommand $command): void
    {
        $session = $this->sessions->findById($command->learnerSessionId);

        if ($session === null) {
            return;
        }

        $session->setTargetedDimension($command->dimensionId);
        $this->sessions->save($session);
    }
}
