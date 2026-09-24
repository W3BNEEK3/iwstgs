<?php
namespace Src\SimExecution\Application\Command\ReturnItemToBacklog;

use Src\SimExecution\Domain\Backlog\LearnerBacklogItemRepository;
use Src\SimExecution\Domain\Board\SprintBoardEventRepository;
use Src\SimExecution\Domain\Board\SprintBoardEventType;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\SimExecution\Domain\Exceptions\BacklogItemNotFoundException;
use Src\SimExecution\Domain\Exceptions\LearnerNotFoundException;

final class ReturnItemToBacklogHandler
{
    public function __construct(
        private readonly LearnerRepository $learners,
        private readonly LearnerBacklogItemRepository $backlogItems,
        private readonly SprintBoardEventRepository $boardEvents,
    ) {}

    public function handle(ReturnItemToBacklogCommand $command): void
    {
        $learner = $command->userId !== null ? $this->learners->findByUserId($command->userId) : null;
        if ($learner === null) {
            throw new LearnerNotFoundException();
        }

        $item = $this->backlogItems->findById($command->itemId);
        if ($item === null || $item->learnerId() !== $learner->id()) {
            throw new BacklogItemNotFoundException();
        }

        $sprintId = $item->sprintId();

        $item->returnToBacklog();
        $this->backlogItems->save($item);

        $this->boardEvents->record(
            learnerId:  $learner->id(),
            sessionId:  $item->learnerSessionId(),
            sprintId:   $sprintId,
            itemId:     $item->id(),
            eventType:  SprintBoardEventType::ItemMovedToSprint,
            fromStatus: 'in_sprint',
            toStatus:   'backlog',
        );
    }
}
