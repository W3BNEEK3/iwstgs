<?php
namespace Src\SimExecution\Application\Command\FireScenarioEvents;

use Src\Shared\Application\Bus\QueryBus;
use Src\Shared\Infrastructure\Feature\FeatureFlagService;
use Src\SimExecution\Domain\Session\LearnerSessionRepository;
use Src\SimExecution\Domain\Session\SessionStatus;
use Src\SimExecution\Domain\Story\LearnerScenarioEventRepository;
use Src\Simulation\Application\Query\ListScenarioEvents\ListScenarioEventsQuery;
use Src\Simulation\Domain\Build\ScenarioEvent;

/**
 * Fires the scripted story beats of the session's current scenario (design
 * doc v2-01 §1.6) — at its start, and after a task is passed. Safe to call
 * repeatedly: each event fires once per session.
 *
 * Role-tagged events (meant for one role on a Work Experience team) are left
 * for the Work Experience track; teammate pull requests arrive with it too,
 * so for now every event is delivered as a message on the learner's board.
 */
final class FireScenarioEventsHandler
{
    public function __construct(
        private readonly LearnerSessionRepository $sessions,
        private readonly LearnerScenarioEventRepository $fired,
        private readonly QueryBus $queryBus,
        private readonly FeatureFlagService $flags,
    ) {}

    public function handle(FireScenarioEventsCommand $command): void
    {
        if (! $this->flags->isEnabled('scenario.scripted_events')) {
            return;
        }

        $session = $this->sessions->findById($command->learnerSessionId);
        if ($session === null || $session->status() !== SessionStatus::Active || $session->currentScenarioId() === null) {
            return;
        }

        /** @var ScenarioEvent $event */
        foreach ($this->queryBus->ask(new ListScenarioEventsQuery($session->currentScenarioId())) as $event) {
            $due = $event->trigger === $command->trigger
                && ($event->trigger !== ScenarioEvent::AFTER_TASK || $event->triggerTaskId === $command->taskId);
            if (! $due || $event->roleTags !== null) {
                continue;
            }

            $this->fired->recordFired($session->id(), $event->id, [
                'event_type' => $event->eventType,
                'sender'     => $event->payload['sender'] ?? 'The team',
                'role'       => $event->payload['role'] ?? null,
                'text'       => $event->payload['text'] ?? '',
            ]);
        }
    }
}
