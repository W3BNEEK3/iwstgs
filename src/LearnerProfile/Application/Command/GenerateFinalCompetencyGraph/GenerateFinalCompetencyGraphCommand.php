<?php

namespace Src\LearnerProfile\Application\Command\GenerateFinalCompetencyGraph;

/**
 * Gap 3 — Fired by ScenarioTransitionService when findNextScenario() returns
 * null, meaning all project scenarios have been completed. Triggers a snapshot
 * of the learner's full competency state for the final profile graph.
 */
final class GenerateFinalCompetencyGraphCommand
{
    public function __construct(
        public readonly string $learnerId,
        public readonly string $learnerSessionId,
    ) {}
}
