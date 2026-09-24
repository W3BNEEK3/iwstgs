<?php
namespace Src\SimExecution\Application\Command\UpdateBacklogItemStatus;

use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Domain\Backlog\BacklogItemStatus;
use Src\SimExecution\Domain\Backlog\LearnerBacklogItem;
use Src\SimExecution\Domain\Backlog\LearnerBacklogItemRepository;
use Src\SimExecution\Domain\Board\InjectedTaskCardRepository;
use Src\SimExecution\Domain\Board\SprintBoardEventRepository;
use Src\SimExecution\Domain\Board\SprintBoardEventType;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\SimExecution\Domain\Exceptions\BacklogItemNotFoundException;
use Src\SimExecution\Domain\Exceptions\InvalidBacklogItemTransitionException;
use Src\SimExecution\Domain\Exceptions\LearnerNotFoundException;
use Src\Simulation\Application\Query\GetBacklogItemTemplate\GetBacklogItemTemplateQuery;

/**
 * The sprint board only ever drives an item forward/back one column at a
 * time (drag onto an adjacent column), so the target status maps directly
 * onto one of LearnerBacklogItem's named transition methods — there's no
 * separate "generic setStatus()" on the aggregate, each transition enforces
 * its own preconditions.
 */
final class UpdateBacklogItemStatusHandler
{
    public function __construct(
        private readonly LearnerRepository $learners,
        private readonly LearnerBacklogItemRepository $backlogItems,
        private readonly SprintBoardEventRepository $boardEvents,
        private readonly InjectedTaskCardRepository $injectedCards,
        private readonly QueryBus $queryBus,
    ) {}

    public function handle(UpdateBacklogItemStatusCommand $command): void
    {
        $learner = $command->userId !== null ? $this->learners->findByUserId($command->userId) : null;
        if ($learner === null) {
            throw new LearnerNotFoundException();
        }

        $item = $this->backlogItems->findById($command->itemId);
        if ($item === null || $item->learnerId() !== $learner->id()) {
            throw new BacklogItemNotFoundException();
        }

        $target = BacklogItemStatus::tryFrom($command->targetStatus);
        if ($target === null) {
            throw new InvalidBacklogItemTransitionException('unrecognised target status');
        }

        if ($target === BacklogItemStatus::Done && $this->hasGradableTask($item)) {
            throw new InvalidBacklogItemTransitionException(
                'an item linked to a task can only be completed by a passing AI evaluation, not marked done manually'
            );
        }

        $fromStatus = $item->status()->value;

        match ($target) {
            BacklogItemStatus::InProgress => $this->transitionToInProgress($item),
            BacklogItemStatus::Done       => $item->complete(),
            BacklogItemStatus::Blocked    => $item->block(),
            BacklogItemStatus::InSprint   => $item->revertToSprint(),
            BacklogItemStatus::Backlog    => throw new InvalidBacklogItemTransitionException('an item cannot be returned to the backlog from the sprint board'),
        };

        $this->backlogItems->save($item);

        $this->boardEvents->record(
            learnerId:  $learner->id(),
            sessionId:  $item->learnerSessionId(),
            sprintId:   $item->sprintId(),
            itemId:     $item->id(),
            eventType:  SprintBoardEventType::ItemStatusChanged,
            fromStatus: $fromStatus,
            toStatus:   $item->status()->value,
        );
    }

    private function transitionToInProgress(LearnerBacklogItem $item): void
    {
        if ($item->status() === BacklogItemStatus::Blocked) {
            $item->unblock();
            return;
        }

        $item->start();
    }

    private function hasGradableTask(LearnerBacklogItem $item): bool
    {
        if ($item->isInjected()) {
            $card = $item->injectedCardId() !== null ? $this->injectedCards->findById($item->injectedCardId()) : null;
            return $card?->injectedTaskId !== null;
        }

        if ($item->templateItemId() === null) {
            return false;
        }

        $template = $this->queryBus->ask(new GetBacklogItemTemplateQuery($item->templateItemId()));
        return $template?->taskId !== null;
    }
}
