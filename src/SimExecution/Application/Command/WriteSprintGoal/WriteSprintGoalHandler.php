<?php
namespace Src\SimExecution\Application\Command\WriteSprintGoal;

use Src\SimExecution\Domain\Board\SprintBoardEventRepository;
use Src\SimExecution\Domain\Board\SprintBoardEventType;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\SimExecution\Domain\Exceptions\LearnerNotFoundException;
use Src\SimExecution\Domain\Exceptions\SprintNotFoundException;
use Src\SimExecution\Domain\Sprint\LearnerSprintRepository;

final class WriteSprintGoalHandler
{
    public function __construct(
        private readonly LearnerRepository $learners,
        private readonly LearnerSprintRepository $sprints,
        private readonly SprintBoardEventRepository $boardEvents,
    ) {}

    public function handle(WriteSprintGoalCommand $command): void
    {
        $learner = $command->userId !== null ? $this->learners->findByUserId($command->userId) : null;
        if ($learner === null) {
            throw new LearnerNotFoundException();
        }

        $sprint = $this->sprints->findById($command->sprintId);
        if ($sprint === null || $sprint->learnerId() !== $learner->id()) {
            throw new SprintNotFoundException();
        }

        $sprint->writeGoal($command->goal);
        $this->sprints->save($sprint);

        $this->boardEvents->record(
            learnerId:  $learner->id(),
            sessionId:  $sprint->learnerSessionId(),
            sprintId:   $sprint->id(),
            itemId:     null,
            eventType:  SprintBoardEventType::SprintGoalWritten,
        );
    }
}
