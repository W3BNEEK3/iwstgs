<?php
namespace Src\SimExecution\Application\Query\GetSessionOnboarding;

use Src\AIMediation\Application\Query\GetOnboardingBriefing\GetOnboardingBriefingQuery;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\SimExecution\Domain\Session\LearnerSessionRepository;
use Src\Simulation\Application\Query\GetProject\GetProjectQuery;
use Src\Simulation\Application\Query\ListScenariosByProject\ListScenariosByProjectQuery;
use Src\Simulation\Domain\Scenario\ScenarioTemplate;

final class GetSessionOnboardingHandler
{
    public function __construct(
        private readonly LearnerRepository $learners,
        private readonly LearnerSessionRepository $sessions,
        private readonly QueryBus $queryBus,
    ) {}

    public function handle(GetSessionOnboardingQuery $query): ?SessionOnboardingView
    {
        $learner = $query->userId !== null ? $this->learners->findByUserId($query->userId) : null;
        if ($learner === null) {
            return null;
        }

        $session = $this->sessions->findByLearnerAndProject($learner->id(), $query->projectId);
        if ($session === null) {
            return null;
        }

        $project = $this->queryBus->ask(new GetProjectQuery($query->projectId));
        if ($project === null || ! $project->isPublished()) {
            // Unpublished projects (e.g. the internal diagnostic-assessment project,
            // whose scenario is deliberately excluded below) have no reachable
            // onboarding page — catalogue-show already 404s these; this closes the
            // same gap for a learner who reaches this URL directly with an existing
            // role enrolment (the diagnostic pathway itself creates one).
            return null;
        }

        /** @var ScenarioTemplate[] $scenarios */
        $scenarios = $this->queryBus->ask(new ListScenariosByProjectQuery($query->projectId));
        $firstScenario = null;
        foreach ($scenarios as $scenario) {
            if (! $scenario->isPublished() || ! $scenario->isActive() || $scenario->isDiagnostic()) {
                continue;
            }
            if ($firstScenario === null || $scenario->sequenceOrder() < $firstScenario->sequenceOrder()) {
                $firstScenario = $scenario;
            }
        }

        $onboardingBriefing = $this->queryBus->ask(new GetOnboardingBriefingQuery($query->projectId));

        return new SessionOnboardingView(
            sessionId:                 $session->id(),
            sessionStatus:             $session->status()->value,
            inductionCompleted:        $session->inductionCompletedAt() !== null,
            projectTitle:              $project->title(),
            businessContext:           $project->businessContext(),
            onboardingBriefing:        $onboardingBriefing,
            scenarioTitle:             $firstScenario?->title(),
            scenarioNarrativeContext:  $firstScenario?->narrativeContext(),
            scenarioSituationTrigger:  $firstScenario?->situationTrigger(),
            scenarioSituationTriggerType: $firstScenario?->situationTriggerType()?->value,
            scenarioLearnerRoleLabel:  $firstScenario?->learnerRoleLabel(),
        );
    }
}
