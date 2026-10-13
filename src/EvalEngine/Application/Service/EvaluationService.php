<?php
namespace Src\EvalEngine\Application\Service;

use Src\AIMediation\Application\EvaluationPromptBuilder;
use Src\AIMediation\Application\EvaluationResultParser;
use Src\AIMediation\Domain\Audit\ActionTaken;
use Src\AIMediation\Domain\Audit\AimediationEventRepository;
use Src\AIMediation\Domain\Audit\TriggerType;
use Src\AIMediation\Domain\Provider\AiEvaluatorClient;
use Src\Competency\Application\Query\ListCompetenceDimensions\ListCompetenceDimensionsQuery;
use Src\EvalEngine\Domain\Evaluation\DimensionEvaluationRepository;
use Src\EvalEngine\Domain\Evaluation\EvaluationComplete;
use Src\EvalEngine\Domain\Evaluation\EvaluationResultRepository;
use Src\EvalEngine\Domain\FollowUp\FollowUpPromptRepository;
use Src\Shared\Application\Bus\QueryBus;
use Src\Simulation\Application\Query\GetTask\GetTaskQuery;
use Src\Submission\Application\Query\GetSubmissionDetail\GetSubmissionDetailQuery;

/**
 * Orchestrates evaluation for a single submission: build prompt -> call the
 * configured AI provider (Claude or Gemini, see AI_EVALUATION_PROVIDER) ->
 * parse -> persist evaluation_results + dimension_evaluations -> log the
 * audit event -> dispatch EvaluationComplete for PostEvaluationRouter.
 *
 * Deliberately lets provider/parsing exceptions propagate — the caller
 * (EvaluateOnSubmissionReceived) decides whether a failed evaluation should
 * fail the whole request; this service just does the evaluation itself.
 */
final class EvaluationService
{
    public function __construct(
        private readonly AiEvaluatorClient $aiEvaluatorClient,
        private readonly EvaluationPromptBuilder $promptBuilder,
        private readonly EvaluationResultParser $parser,
        private readonly EvaluationResultRepository $evaluationResults,
        private readonly DimensionEvaluationRepository $dimensionEvaluations,
        private readonly AimediationEventRepository $aimediationEvents,
        private readonly FollowUpPromptRepository $followUpPrompts,
        private readonly QueryBus $queryBus,
    ) {}

    public function evaluate(string $submissionId, string $learnerId, string $learnerSessionId, string $taskId): void
    {
        $systemPrompt = $this->promptBuilder->systemPrompt();
        $userContent = $this->promptBuilder->userContent($submissionId);

        $rawResponse = $this->aiEvaluatorClient->evaluate($systemPrompt, $userContent);
        $parsed = $this->parser->parse($rawResponse);

        // v2 milestones (design doc v2-01 §5.2): the reviewer's tier and the commit's
        // acceptance tests must both pass. The tier still stands as feedback.
        [$passes, $gapType, $isRegression] = $this->applyAcceptanceTests($submissionId, $parsed->passesThreshold, $parsed->gapType);

        $followUpPromptId = null;
        if ($parsed->isUncertain) {
            $task = $this->queryBus->ask(new GetTaskQuery($taskId));
            $domain = $task?->toPrimitives()['domain'] ?? null;
            $followUpPromptId = $this->followUpPrompts->selectForDomain($domain);
        }

        $evaluationId = $this->evaluationResults->create(
            submissionId:      $submissionId,
            learnerId:         $learnerId,
            overallTier:       $parsed->overallTier,
            passesThreshold:   $passes,
            gapType:           $gapType,
            isUncertain:       $parsed->isUncertain,
            followUpPromptId:  $followUpPromptId,
        );

        $validDimensionIds = collect($this->queryBus->ask(new ListCompetenceDimensionsQuery()))->pluck('id')->all();

        foreach ($parsed->dimensions as $dimension) {
            // A dimension_id Claude echoed back that doesn't match a real competence
            // dimension can't be inserted (FK restrict) — skip rather than fail the
            // whole evaluation over one malformed entry.
            if (! in_array($dimension->dimensionId, $validDimensionIds, true)) {
                continue;
            }

            $this->dimensionEvaluations->create(
                evaluationId:        $evaluationId,
                dimensionId:         $dimension->dimensionId,
                taskDimensionLabel:  $dimension->taskDimensionLabel,
                tierAchieved:        $dimension->tierAchieved,
                criteriaMet:         $dimension->criteriaMet,
                criteriaMissed:      $dimension->criteriaMissed,
                layerScores:         $dimension->layerScores,
                evaluatorNotes:      $dimension->evaluatorNotes,
            );
        }

        $this->aimediationEvents->record(
            learnerId:        $learnerId,
            sessionId:        $learnerSessionId,
            triggerType:      TriggerType::SubmissionReceived,
            triggerSourceId:  $submissionId,
            actionTaken:      ActionTaken::NoAction,
            actionDetail:     ['overall_tier' => $parsed->overallTier, 'passes_threshold' => $passes],
            isDeterministic:  false,
        );

        event(new EvaluationComplete(
            evaluationId:              $evaluationId,
            submissionId:              $submissionId,
            learnerId:                 $learnerId,
            learnerSessionId:          $learnerSessionId,
            taskId:                    $taskId,
            overallTier:               $parsed->overallTier,
            passesThreshold:           $passes,
            isUncertain:               $parsed->isUncertain,
            gapType:                   $gapType,
            knowledgeAnchorsDetected:  $parsed->knowledgeAnchorsDetected,
            isRegression:              $isRegression,
        ));
    }

    /**
     * @return array{0: bool, 1: ?string, 2: bool} passes, gap type, regression
     */
    private function applyAcceptanceTests(string $submissionId, bool $aiPasses, ?string $aiGapType): array
    {
        $detail = $this->queryBus->ask(new GetSubmissionDetailQuery($submissionId));
        if ($detail === null || ! $detail->submission->isFromRepository()) {
            return [$aiPasses, $aiGapType, false];
        }

        $testsPass = $detail->submission->ciStatus === 'passed';
        $isRegression = ($detail->submission->ciReport['regressions'] ?? []) !== [];

        // Good work that the tests reject is usually a process miss (not checking it
        // runs, not running the tests) rather than missing knowledge.
        $gapType = $aiPasses && ! $testsPass ? 'strategy_gap' : $aiGapType;

        return [$aiPasses && $testsPass, $gapType, $isRegression];
    }
}
