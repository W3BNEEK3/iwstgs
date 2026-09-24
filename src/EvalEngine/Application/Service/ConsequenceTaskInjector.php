<?php
namespace Src\EvalEngine\Application\Service;

use Src\AIMediation\Application\Service\GenerateIncidentTicketService;
use Src\AIMediation\Domain\Audit\ActionTaken;
use Src\AIMediation\Domain\Audit\AimediationEventRepository;
use Src\AIMediation\Domain\Audit\TriggerType;
use Src\EvalEngine\Domain\Evaluation\EvaluationComplete;
use Src\EvalEngine\Domain\Gap\GapFlagRepository;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Command\InjectAdaptiveTask\InjectAdaptiveTaskCommand;
use Src\SimExecution\Application\Query\GetLearnerSession\GetLearnerSessionQuery;
use Src\Simulation\Application\Query\GetTask\GetTaskQuery;
use Src\Simulation\Domain\Task\Task;

/**
 * Implementation Plan §9.2. Called by PostEvaluationRouter on a failing
 * evaluation: raises a GapFlag for every dimension that scored below
 * proficient (regardless of whether a consequence task exists to inject —
 * the gap itself is real either way), then, if the failing task has
 * consequence_task_ids configured, injects one onto the learner's board —
 * framed by a Tiroco-generated incident ticket (GenerateIncidentTicketService)
 * rather than a direct rubric reveal, matching the authentic-engineering-
 * feedback design decided with the user: no diagnosis handed over, only a
 * downstream symptom.
 *
 * "Selects gap-type-appropriate variant (knowledge_gap → conceptual,
 * strategy_gap → process)" per the plan has no real way to tell which
 * configured task IS conceptual vs process — Task carries no such
 * classification field. Resolved with a documented, deterministic
 * simplification: first entry for knowledge_gap, last for strategy_gap,
 * single entry used regardless of gap_type, none configured skips
 * injection but still raises the gap flag(s).
 */
final class ConsequenceTaskInjector
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly CommandBus $commandBus,
        private readonly GapFlagRepository $gapFlags,
        private readonly AimediationEventRepository $aimediationEvents,
        private readonly GenerateIncidentTicketService $incidentTickets,
    ) {}

    /** @param \Src\EvalEngine\Domain\Evaluation\DimensionEvaluationSummary[] $failingDimensions dimensions that scored below proficient on this evaluation */
    public function inject(EvaluationComplete $event, array $failingDimensions): void
    {
        foreach ($failingDimensions as $dimension) {
            $this->gapFlags->create(
                learnerId:        $event->learnerId,
                dimensionId:      $dimension->dimensionId,
                subCriterionId:   null,
                sourceTaskId:     $event->taskId,
                sourceSessionId:  $event->learnerSessionId,
            );
        }

        $task = $this->queryBus->ask(new GetTaskQuery($event->taskId));
        $consequenceTaskIds = $task?->toPrimitives()['consequence_task_ids'] ?? [];

        if ($consequenceTaskIds === []) {
            $this->logNoAction($event, 'no consequence_task_ids configured for this task');
            return;
        }

        $selectedTaskId = $this->selectVariant($consequenceTaskIds, $event->gapType);
        /** @var Task|null $selectedTask */
        $selectedTask = $this->queryBus->ask(new GetTaskQuery($selectedTaskId));

        if ($selectedTask === null || ! $selectedTask->isPublished()) {
            $this->logNoAction($event, "configured consequence task {$selectedTaskId} is missing or unpublished");
            return;
        }

        $session = $this->queryBus->ask(new GetLearnerSessionQuery($event->learnerSessionId));

        $incidentTicketText = $this->incidentTickets->generate($selectedTask, $failingDimensions);

        $this->commandBus->dispatch(new InjectAdaptiveTaskCommand(
            learnerSessionId: $event->learnerSessionId,
            learnerId:        $event->learnerId,
            cardType:         'consequence',
            sourceTaskId:     $event->taskId,
            injectedTaskId:   $selectedTaskId,
            targetSprintId:   $session?->currentSprintId,
            visualTreatment:  'consequence_amber',
            suggestionType:   null,
            priority:         'must_have',
            generatedTaskContent: ['incident_ticket_text' => $incidentTicketText],
        ));

        $this->aimediationEvents->record(
            learnerId:        $event->learnerId,
            sessionId:        $event->learnerSessionId,
            triggerType:      TriggerType::FailureDetected,
            triggerSourceId:  $event->submissionId,
            actionTaken:      ActionTaken::ConsequenceInjected,
            actionDetail:     ['selected_task_id' => $selectedTaskId, 'gap_type' => $event->gapType, 'incident_ticket_generated' => true],
            // Task selection is deterministic (selectVariant() above); the incident
            // ticket text itself is a real generative decision, hence false overall.
            isDeterministic:  false,
        );
    }

    /** @param string[] $ids */
    private function selectVariant(array $ids, ?string $gapType): string
    {
        if (count($ids) === 1) {
            return $ids[0];
        }

        return match ($gapType) {
            'knowledge_gap' => $ids[0],
            'strategy_gap'  => $ids[count($ids) - 1],
            default         => $ids[array_rand($ids)],
        };
    }

    private function logNoAction(EvaluationComplete $event, string $note): void
    {
        $this->aimediationEvents->record(
            learnerId:        $event->learnerId,
            sessionId:        $event->learnerSessionId,
            triggerType:      TriggerType::FailureDetected,
            triggerSourceId:  $event->submissionId,
            actionTaken:      ActionTaken::NoAction,
            actionDetail:     ['note' => $note],
            isDeterministic:  true,
        );
    }
}
