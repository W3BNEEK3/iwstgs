<?php
namespace Src\SimExecution\Application\Command\MoveItemToSprint;

use Src\SimExecution\Domain\Backlog\LearnerBacklogItemRepository;
use Src\SimExecution\Domain\Board\SprintBoardEventRepository;
use Src\SimExecution\Domain\Board\SprintBoardEventType;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\SimExecution\Domain\Exceptions\BacklogItemNotFoundException;
use Src\SimExecution\Domain\Exceptions\InvalidSprintTransitionException;
use Src\SimExecution\Domain\Exceptions\LearnerNotFoundException;
use Src\SimExecution\Domain\Exceptions\SprintNotFoundException;
use Src\SimExecution\Domain\Sprint\LearnerSprintRepository;
use Src\SimExecution\Domain\Sprint\SprintStatus;

final class MoveItemToSprintHandler
{
    public function __construct(
        private readonly LearnerRepository $learners,
        private readonly LearnerBacklogItemRepository $backlogItems,
        private readonly LearnerSprintRepository $sprints,
        private readonly SprintBoardEventRepository $boardEvents,
    ) {}

    public function handle(MoveItemToSprintCommand $command): void
    {
        $learner = $command->userId !== null ? $this->learners->findByUserId($command->userId) : null;
        if ($learner === null) {
            throw new LearnerNotFoundException();
        }

        $item = $this->backlogItems->findById($command->itemId);
        if ($item === null || $item->learnerId() !== $learner->id()) {
            throw new BacklogItemNotFoundException();
        }

        $sprint = $this->sprints->findById($command->sprintId);
        if ($sprint === null || $sprint->learnerId() !== $learner->id()) {
            throw new SprintNotFoundException();
        }

        if ($sprint->status() !== SprintStatus::Planning) {
            throw new InvalidSprintTransitionException('items can only be moved into a sprint while it is being planned');
        }

        $item->moveToSprint($sprint->id());
        $this->backlogItems->save($item);

        $this->boardEvents->record(
            learnerId:  $learner->id(),
            sessionId:  $item->learnerSessionId(),
            sprintId:   $sprint->id(),
            itemId:     $item->id(),
            eventType:  SprintBoardEventType::ItemMovedToSprint,
            fromStatus: 'backlog',
            toStatus:   'in_sprint',
        );
    }
}
