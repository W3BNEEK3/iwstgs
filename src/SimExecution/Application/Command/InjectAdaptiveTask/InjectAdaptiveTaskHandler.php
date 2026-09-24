<?php
namespace Src\SimExecution\Application\Command\InjectAdaptiveTask;

use Illuminate\Support\Facades\DB;
use Src\SimExecution\Domain\Backlog\BacklogPriority;
use Src\SimExecution\Domain\Backlog\LearnerBacklogItem;
use Src\SimExecution\Domain\Backlog\LearnerBacklogItemId;
use Src\SimExecution\Domain\Backlog\LearnerBacklogItemRepository;
use Src\SimExecution\Domain\Board\InjectedTaskCardRepository;

/**
 * System-triggered, like CompleteBacklogItemFromEvaluation — no learner
 * userId to authorize against. Called by ConsequenceTaskInjector (EvalEngine)
 * and SuggestionTaskInjector (AIMediation) via CommandBus; creates the
 * injected_task_cards row and its matching LearnerBacklogItem atomically,
 * dropping the new item straight into the target sprint when one is given.
 */
final class InjectAdaptiveTaskHandler
{
    public function __construct(
        private readonly InjectedTaskCardRepository $cards,
        private readonly LearnerBacklogItemRepository $backlogItems,
    ) {}

    public function handle(InjectAdaptiveTaskCommand $command): void
    {
        DB::transaction(function () use ($command) {
            $cardId = $this->cards->create(
                learnerSessionId:      $command->learnerSessionId,
                learnerId:             $command->learnerId,
                cardType:              $command->cardType,
                sourceTaskId:          $command->sourceTaskId,
                injectedTaskId:        $command->injectedTaskId,
                generatedTaskContent:  $command->generatedTaskContent,
                targetSprintId:        $command->targetSprintId,
                visualTreatment:       $command->visualTreatment,
                suggestionType:        $command->suggestionType,
            );

            $item = LearnerBacklogItem::seedFromInjection(
                id:               LearnerBacklogItemId::generate(),
                learnerSessionId: $command->learnerSessionId,
                learnerId:        $command->learnerId,
                injectedCardId:   $cardId,
                priority:         BacklogPriority::from($command->priority),
            );

            if ($command->targetSprintId !== null) {
                $item->moveToSprint($command->targetSprintId);
            }

            $this->backlogItems->save($item);
        });
    }
}
