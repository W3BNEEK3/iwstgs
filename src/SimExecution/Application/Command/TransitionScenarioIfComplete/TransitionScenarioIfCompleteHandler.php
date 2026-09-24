<?php
namespace Src\SimExecution\Application\Command\TransitionScenarioIfComplete;

use Src\Shared\Infrastructure\Feature\FeatureFlagService;
use Src\SimExecution\Application\Service\ScenarioTransitionService;

final class TransitionScenarioIfCompleteHandler
{
    public function __construct(
        private readonly ScenarioTransitionService $scenarioTransitionService,
        private readonly FeatureFlagService $flags,
    ) {}

    public function handle(TransitionScenarioIfCompleteCommand $command): void
    {
        if (! $this->flags->isEnabled('adaptive.scenario_transitions')) {
            return;
        }

        $this->scenarioTransitionService->transitionIfScenarioComplete($command->learnerSessionId);
    }
}
