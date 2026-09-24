<?php
namespace Src\SimExecution\Application\Command\ConfirmSprint;

use Illuminate\Support\Facades\DB;
use Src\SimExecution\Domain\Board\SprintBoardEventRepository;
use Src\SimExecution\Domain\Board\SprintBoardEventType;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\SimExecution\Domain\Exceptions\LearnerNotFoundException;
use Src\SimExecution\Domain\Exceptions\SessionNotFoundException;
use Src\SimExecution\Domain\Exceptions\SprintNotFoundException;
use Src\SimExecution\Domain\Session\LearnerSessionRepository;
use Src\SimExecution\Domain\Sprint\LearnerSprintRepository;

final class ConfirmSprintHandler
{
    public function __construct(
        private readonly LearnerRepository $learners,
        private readonly LearnerSprintRepository $sprints,
        private readonly LearnerSessionRepository $sessions,
        private readonly SprintBoardEventRepository $boardEvents,
    ) {}

    public function handle(ConfirmSprintCommand $command): void
    {
        $learner = $command->userId !== null ? $this->learners->findByUserId($command->userId) : null;
        if ($learner === null) {
            throw new LearnerNotFoundException();
        }

        $sprint = $this->sprints->findById($command->sprintId);
        if ($sprint === null || $sprint->learnerId() !== $learner->id()) {
            throw new SprintNotFoundException();
        }

        $session = $this->sessions->findById($sprint->learnerSessionId());
        if ($session === null) {
            throw new SessionNotFoundException();
        }

        DB::transaction(function () use ($sprint, $session) {
            $sprint->confirm();
            $this->sprints->save($sprint);

            $session->advanceToSprint($sprint->id());
            $this->sessions->save($session);
        });

        foreach ($session->releaseEvents() as $event) {
            event($event);
        }

        $this->boardEvents->record(
            learnerId: $learner->id(),
            sessionId: $sprint->learnerSessionId(),
            sprintId:  $sprint->id(),
            itemId:    null,
            eventType: SprintBoardEventType::SprintConfirmed,
        );
    }
}
