<?php
namespace Src\SimExecution\Application\Query\GetSessionOnboarding;

final class SessionOnboardingView
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $sessionStatus,
        public readonly bool $inductionCompleted,
        public readonly string $projectTitle,
        public readonly string $businessContext,
        public readonly ?string $onboardingBriefing,
        public readonly ?string $scenarioTitle,
        public readonly ?string $scenarioNarrativeContext,
        public readonly ?string $scenarioSituationTrigger,
        public readonly ?string $scenarioSituationTriggerType,
        public readonly ?string $scenarioLearnerRoleLabel,
    ) {}
}
