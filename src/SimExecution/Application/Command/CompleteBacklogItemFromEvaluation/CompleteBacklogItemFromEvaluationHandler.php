<?php
namespace Src\SimExecution\Application\Command\CompleteBacklogItemFromEvaluation;

use Src\SimExecution\Domain\Backlog\BacklogItemStatus;
use Src\SimExecution\Domain\Backlog\LearnerBacklogItemRepository;

/**
 * System-triggered — no learner userId to authorize against, unlike every
 * other backlog command. PostEvaluationRouter calls this after a passing
 * evaluation, using the exact backlog item id it already resolved via
 * FindBacklogItemForTaskQuery; there is no HTTP request or Auth::id()
 * involved, so it doesn't go through the ownership-checked command path.
 */
final class CompleteBacklogItemFromEvaluationHandler
{
    public function __construct(private readonly LearnerBacklogItemRepository $backlogItems) {}

    public function handle(CompleteBacklogItemFromEvaluationCommand $command): void
    {
        $item = $this->backlogItems->findById($command->backlogItemId);
        if ($item === null || $item->status() === BacklogItemStatus::Done) {
            return;
        }

        if ($item->status() === BacklogItemStatus::InSprint) {
            $item->start();
        }

        $item->complete();
        $this->backlogItems->save($item);
    }
}
