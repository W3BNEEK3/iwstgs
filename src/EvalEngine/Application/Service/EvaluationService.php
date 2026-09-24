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
            passesThreshold:   $parsed->passesThreshold,
            gapType:           $parsed->gapType,
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
            actionDetail:     ['overall_tier' => $parsed->overallTier, 'passes_threshold' => $parsed->passesThreshold],
            isDeterministic:  false,
        );

        event(new EvaluationComplete(
            evaluationId:              $evaluationId,
            submissionId:              $submissionId,
            learnerId:                 $learnerId,
            learnerSessionId:          $learnerSessionId,
            taskId:                    $taskId,
            overallTier:               $parsed->overallTier,
            passesThreshold:           $parsed->passesThreshold,
            isUncertain:               $parsed->isUncertain,
            gapType:                   $parsed->gapType,
            knowledgeAnchorsDetected:  $parsed->knowledgeAnchorsDetected,
        ));
    }
}
