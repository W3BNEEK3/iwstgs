<?php
namespace Src\AIMediation\Application\Service;

use Src\AIMediation\Domain\Audit\ActionTaken;
use Src\AIMediation\Domain\Audit\AimediationEventRepository;
use Src\AIMediation\Domain\Audit\TriggerType;
use Src\AIMediation\Domain\Habit\HabitFlagRepository;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Command\InjectAdaptiveTask\InjectAdaptiveTaskCommand;
use Src\SimExecution\Application\Query\GetLatestInjectedCardId\GetLatestInjectedCardIdQuery;
use Src\Simulation\Application\Query\GetTask\GetTaskQuery;

/**
 * Implementation Plan §9.3, second half. Called by ScenarioTransitionService
 * (§9.5), not immediately when a habit is detected — every queued HabitFlag
 * (recorded by HabitPatternDetector, no suggestion card attached yet) gets
 * a real suggestion task injected here, picked from its source task's own
 * suggestion_task_ids (see HabitFlagRepository's docblock for why the
 * flag tracks a source task rather than a dimension directly).
 */
final class SuggestionTaskInjector
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly CommandBus $commandBus,
        private readonly HabitFlagRepository $habitFlags,
        private readonly AimediationEventRepository $aimediationEvents,
    ) {}

    public function injectQueuedSuggestions(string $learnerId, string $learnerSessionId, ?string $targetSprintId): void
    {
        foreach ($this->habitFlags->findQueuedForSuggestion($learnerId) as $flag) {
            if ($flag->sourceTaskId === null) {
                continue;
            }

            $sourceTask = $this->queryBus->ask(new GetTaskQuery($flag->sourceTaskId));
            $suggestionTaskIds = $sourceTask?->toPrimitives()['suggestion_task_ids'] ?? [];
            if ($suggestionTaskIds === []) {
                continue;
            }

            $selectedTaskId = $suggestionTaskIds[array_rand($suggestionTaskIds)];
            $selectedTask = $this->queryBus->ask(new GetTaskQuery($selectedTaskId));
            if ($selectedTask === null || ! $selectedTask->isPublished()) {
                continue;
            }

            $this->commandBus->dispatch(new InjectAdaptiveTaskCommand(
                learnerSessionId: $learnerSessionId,
                learnerId:        $learnerId,
                cardType:         'suggestion',
                sourceTaskId:     $flag->sourceTaskId,
                injectedTaskId:   $selectedTaskId,
                targetSprintId:   $targetSprintId,
                visualTreatment:  'suggestion_teal',
                suggestionType:   'corrective',
                priority:         'should_have',
            ));

            $newCardId = $this->queryBus->ask(new GetLatestInjectedCardIdQuery($learnerSessionId, $flag->sourceTaskId));
            if ($newCardId !== null) {
                $this->habitFlags->attachSuggestionCard($flag->id, $newCardId);
            }

            $this->aimediationEvents->record(
                learnerId:        $learnerId,
                sessionId:        $learnerSessionId,
                triggerType:      TriggerType::HabitDetected,
                triggerSourceId:  $flag->id,
                actionTaken:      ActionTaken::SuggestionQueued,
                actionDetail:     ['selected_task_id' => $selectedTaskId, 'habit_description' => $flag->habitDescription],
                isDeterministic:  true,
            );
        }
    }
}
