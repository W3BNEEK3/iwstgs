<?php
namespace Src\EvalEngine\Application\Service;

use Src\AIMediation\Application\Command\RecordConceptTagRecommendation\RecordConceptTagRecommendationCommand;
use Src\AIMediation\Application\Service\HabitPatternDetector;
use Src\AIMediation\Domain\Audit\ActionTaken;
use Src\AIMediation\Domain\Audit\AimediationEventRepository;
use Src\AIMediation\Domain\Audit\TriggerType;
use Src\AIMediation\Domain\Review\HumanReviewEntry;
use Src\AIMediation\Domain\Review\HumanReviewEntryId;
use Src\AIMediation\Domain\Review\HumanReviewRepository;
use Src\EvalEngine\Domain\Evaluation\EvaluationComplete;
use Src\EvalEngine\Domain\Evaluation\EvaluationResultRepository;
use Src\EvalEngine\Domain\FollowUp\FollowUpPromptRepository;
use Src\EvalEngine\Domain\Gap\GapFlagRepository;
use Src\LearnerProfile\Application\Command\AdjustCacFromSubmission\AdjustCacFromSubmissionCommand;
use Src\LearnerProfile\Application\Command\RecordEvaluationOutcome\RecordEvaluationOutcomeCommand;
use Src\LearnerProfile\Application\Command\UpdateConceptMastery\UpdateConceptMasteryCommand;
use Src\LearnerProfile\Application\Command\UpdateDimensionScore\UpdateDimensionScoreCommand;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\Shared\Infrastructure\Feature\FeatureFlagService;
use Src\SimExecution\Application\Command\CompleteBacklogItemFromEvaluation\CompleteBacklogItemFromEvaluationCommand;
use Src\SimExecution\Application\Command\TransitionScenarioIfComplete\TransitionScenarioIfCompleteCommand;
use Src\SimExecution\Application\Query\FindBacklogItemForTask\FindBacklogItemForTaskQuery;
use Src\SimExecution\Application\Query\FindDiagnosticSessionForTask\FindDiagnosticSessionForTaskQuery;
use Src\SimExecution\Application\Query\GetLearnerSession\GetLearnerSessionQuery;

/**
 * Implementation Plan §8.4 + Phase 9 (Adaptive Engine). Reacts to
 * EvaluationComplete:
 *   - always: update dimension_scores, update failure_streak (+ rank review
 *     flag if the streak crosses threshold — Phase 8); behind
 *     adaptive.rank_management: check for escalation (§9.4) and mismatch
 *     (§9.4); behind adaptive.suggestion_tasks: check for a habit pattern (§9.3)
 *   - pass: mark the linked backlog item done; resolve any open gap flags
 *     for dimensions this submission scored proficient+ on; check whether
 *     the scenario is now complete (§9.5, behind adaptive.scenario_transitions)
 *   - fail: raise gap flags for dimensions below proficient, and (behind
 *     adaptive.consequence_tasks) inject a real consequence task via
 *     ConsequenceTaskInjector (§9.2)
 *   - uncertain: write follow_up_prompt_text + follow_up_status='pending' to
 *     the evaluation result so the learner can answer; queue HumanReviewEntry
 *     as a fallback only if the follow-up is never answered (Gap 2 fix —
 *     Integration Spec §12 step 8C).
 *   - knowledge anchors Claude detected: update concept_mastery_records
 *   - diagnostic: if this task belongs to an in-progress diagnostic session,
 *     skip backlog/consequence/suggestion/escalation routing entirely and
 *     hand off to RankAssignmentService instead (Implementation Plan §5.5)
 */
final class PostEvaluationRouter
{
    private const PASSING_TIERS = ['proficient', 'distinguished'];

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
        private readonly EvaluationResultRepository $evaluationResults,
        private readonly AimediationEventRepository $aimediationEvents,
        private readonly HumanReviewRepository $humanReviews,
        private readonly GapFlagRepository $gapFlags,
        private readonly ConsequenceTaskInjector $consequenceTaskInjector,
        private readonly RankEscalationService $rankEscalationService,
        private readonly MismatchDetector $mismatchDetector,
        private readonly HabitPatternDetector $habitPatternDetector,
        private readonly FeatureFlagService $flags,
        private readonly RankAssignmentService $rankAssignmentService,
        private readonly FollowUpPromptRepository $followUpPrompts,
    ) {}

    public function handle(EvaluationComplete $event): void
    {
        $this->commandBus->dispatch(new RecordEvaluationOutcomeCommand($event->learnerId, $event->passesThreshold));

        $evaluation = $this->evaluationResults->findBySubmissionId($event->submissionId);
        $dimensions = $evaluation?->dimensions ?? [];

        foreach ($dimensions as $dimension) {
            $this->commandBus->dispatch(new UpdateDimensionScoreCommand(
                $event->learnerId,
                $dimension->dimensionId,
                $dimension->tierAchieved,
            ));
        }

        foreach ($event->knowledgeAnchorsDetected as $anchor) {
            if ($anchor['conceptId'] === null) {
                continue;
            }
            $this->commandBus->dispatch(new UpdateConceptMasteryCommand(
                $event->learnerId,
                $anchor['conceptId'],
                $anchor['met'],
            ));
        }

        $diagnosticSessionId = $this->queryBus->ask(new FindDiagnosticSessionForTaskQuery(
            learnerSessionId: $event->learnerSessionId,
            taskId:           $event->taskId,
        ));

        if ($diagnosticSessionId !== null) {
            if ($event->isUncertain) {
                $this->routeUncertain($event);
            }

            $this->rankAssignmentService->maybeAssignFromDiagnostic($event->learnerId, $event->learnerSessionId, $diagnosticSessionId);
            return; // diagnostic tasks never enter backlog/consequence/suggestion/escalation routing
        }

        if ($event->passesThreshold) {
            $this->routePass($event, $dimensions);
        } else {
            $this->routeFail($event, $dimensions);
        }

        if ($event->isUncertain) {
            $this->routeUncertain($event);
        }

        if ($this->flags->isEnabled('adaptive.rank_management')) {
            $this->rankEscalationService->checkForEscalation($event);
            $this->mismatchDetector->detect($event);
        }

        if ($this->flags->isEnabled('adaptive.suggestion_tasks')) {
            $this->habitPatternDetector->detect($event->learnerId, $event->taskId, $dimensions);
        }
    }

    private function routePass(EvaluationComplete $event, array $dimensions): void
    {
        // BLD §7.3 — dispatched unconditionally, ahead of the backlog-item
        // lookup below, so a passing submission always counts toward CAC
        // escalation even for task types (suggestion, consequence) that
        // don't have a backlog item to complete.
        $this->commandBus->dispatch(new AdjustCacFromSubmissionCommand($event->learnerId, true));

        foreach ($dimensions as $dimension) {
            if (in_array($dimension->tierAchieved, self::PASSING_TIERS, true)) {
                $this->gapFlags->resolveOpenForDimension($event->learnerId, $dimension->dimensionId);
            }
        }

        $session = $this->queryBus->ask(new GetLearnerSessionQuery($event->learnerSessionId));
        if ($session === null) {
            return;
        }

        $backlogItemId = $this->queryBus->ask(new FindBacklogItemForTaskQuery(
            learnerSessionId: $event->learnerSessionId,
            taskId:           $event->taskId,
            projectId:        $session->projectId,
        ));

        if ($backlogItemId === null) {
            return;
        }

        $this->commandBus->dispatch(new CompleteBacklogItemFromEvaluationCommand($backlogItemId));

        $this->aimediationEvents->record(
            learnerId:        $event->learnerId,
            sessionId:        $event->learnerSessionId,
            triggerType:      TriggerType::SubmissionReceived,
            triggerSourceId:  $event->submissionId,
            actionTaken:      ActionTaken::TaskCompleted,
            actionDetail:     ['task_id' => $event->taskId, 'backlog_item_id' => $backlogItemId],
            isDeterministic:  true,
        );

        $this->commandBus->dispatch(new TransitionScenarioIfCompleteCommand($event->learnerSessionId));

        // Gap 4 — if Claude suggested novel concepts on a distinguished submission,
        // record them for curator review in concept_tag_recommendations.
        if ($event->overallTier === 'distinguished' && $event->conceptSuggestions !== []) {
            $this->commandBus->dispatch(new RecordConceptTagRecommendationCommand(
                submissionId: $event->submissionId,
                taskId:       $event->taskId,
                learnerId:    $event->learnerId,
                suggestions:  $event->conceptSuggestions,
            ));
        }
    }

    private function routeFail(EvaluationComplete $event, array $dimensions): void
    {
        // Resets the success streak regardless of whether consequence tasks
        // are enabled below — a failure is a failure for CAC purposes either way.
        $this->commandBus->dispatch(new AdjustCacFromSubmissionCommand($event->learnerId, false));

        if (! $this->flags->isEnabled('adaptive.consequence_tasks')) {
            $this->aimediationEvents->record(
                learnerId:        $event->learnerId,
                sessionId:        $event->learnerSessionId,
                triggerType:      TriggerType::FailureDetected,
                triggerSourceId:  $event->submissionId,
                actionTaken:      ActionTaken::NoAction,
                actionDetail:     ['note' => 'adaptive.consequence_tasks is disabled — failure streak was recorded, no consequence task injected.'],
                isDeterministic:  true,
            );
            return;
        }

        $failingDimensions = array_values(array_filter(
            $dimensions,
            fn ($d) => ! in_array($d->tierAchieved, self::PASSING_TIERS, true),
        ));

        $this->consequenceTaskInjector->inject($event, $failingDimensions);
    }

    private function routeUncertain(EvaluationComplete $event): void
    {
        // Gap 2 (Integration Spec §12 step 8C) — before queuing for human review,
        // write the follow-up prompt text to the evaluation result so the learner
        // gets a chance to clarify. The human review entry is created immediately
        // as a safety net; if the learner never answers, the reviewer sees it.
        $promptId = $this->evaluationResults->findBySubmissionId($event->submissionId)?->followUpPromptId;
        $promptText = $promptId !== null ? $this->followUpPrompts->findTextById($promptId) : null;

        if ($promptText !== null) {
            $this->evaluationResults->writeFollowUpPrompt(
                evaluationId:       $event->evaluationId,
                followUpPromptText: $promptText,
                status:             'pending',
            );
        }

        $entry = HumanReviewEntry::queue(
            id:            HumanReviewEntryId::generate(),
            submissionId:  $event->submissionId,
            evaluationId:  $event->evaluationId,
            learnerId:     $event->learnerId,
        );
        $this->humanReviews->save($entry);

        $this->aimediationEvents->record(
            learnerId:        $event->learnerId,
            sessionId:        $event->learnerSessionId,
            triggerType:      TriggerType::UncertainEvaluation,
            triggerSourceId:  $event->submissionId,
            actionTaken:      $promptTemplate !== null
                ? ActionTaken::FollowUpPromptIssued
                : ActionTaken::HumanReviewQueued,
            actionDetail:     $promptTemplate !== null
                ? ['follow_up_prompt' => $promptTemplate->promptText()]
                : null,
            isDeterministic:  true,
        );
    }
}
