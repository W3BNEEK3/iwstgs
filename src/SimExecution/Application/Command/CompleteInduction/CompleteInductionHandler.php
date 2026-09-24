<?php
namespace Src\SimExecution\Application\Command\CompleteInduction;

use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\SimExecution\Domain\Exceptions\LearnerNotFoundException;
use Src\SimExecution\Domain\Exceptions\SessionNotFoundException;
use Src\SimExecution\Domain\Session\LearnerSessionRepository;
use Src\Simulation\Application\Query\ListScenariosByProject\ListScenariosByProjectQuery;
use Src\Simulation\Domain\Scenario\ScenarioTemplate;

/**
 * Moves a session out of induction into the first real (non-diagnostic)
 * scenario. The diagnostic pathway itself is deferred to Phase 8 — this
 * command is what lets a learner actually start work in the meantime.
 */
final class CompleteInductionHandler
{
    public function __construct(
        private readonly LearnerRepository $learners,
        private readonly LearnerSessionRepository $sessions,
        private readonly QueryBus $queryBus,
    ) {}

    public function handle(CompleteInductionCommand $command): void
    {
        $learner = $command->userId !== null ? $this->learners->findByUserId($command->userId) : null;
        if ($learner === null) {
            throw new LearnerNotFoundException();
        }

        $session = $this->sessions->findByLearnerAndProject($learner->id(), $command->projectId);
        if ($session === null) {
            throw new SessionNotFoundException();
        }

        /** @var ScenarioTemplate[] $scenarios */
        $scenarios = $this->queryBus->ask(new ListScenariosByProjectQuery($command->projectId));
        $firstScenario = null;
        foreach ($scenarios as $scenario) {
            if (! $scenario->isPublished() || ! $scenario->isActive() || $scenario->isDiagnostic()) {
                continue;
            }
            if ($firstScenario === null || $scenario->sequenceOrder() < $firstScenario->sequenceOrder()) {
                $firstScenario = $scenario;
            }
        }

        $session->completeInduction($firstScenario?->id());
        $this->sessions->save($session);

        foreach ($session->releaseEvents() as $event) {
            event($event);
        }
    }
}
