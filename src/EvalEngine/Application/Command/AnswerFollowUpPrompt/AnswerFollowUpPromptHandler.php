<?php
namespace Src\EvalEngine\Application\Command\AnswerFollowUpPrompt;

use Src\EvalEngine\Application\Service\EvaluationService;
use Src\EvalEngine\Domain\Evaluation\EvaluationResultRepository;

/**
 * Gap 2 — Handles a learner's follow-up answer by:
 *   1. Marking follow_up_status = 'answered' on the evaluation result.
 *   2. Re-triggering evaluation with the combined original submission + answer,
 *      so Claude can make a more confident determination.
 *
 * If the re-evaluation is still uncertain, the existing HumanReviewEntry
 * (created by routeUncertain() as a safety net) remains in the queue for
 * a human to resolve. No second follow-up is issued — one clarification
 * cycle is the system's limit per Integration Spec §12.
 */
final class AnswerFollowUpPromptHandler
{
    public function __construct(
        private readonly EvaluationResultRepository $evaluationResults,
        private readonly EvaluationService $evaluationService,
    ) {}

    public function handle(AnswerFollowUpPromptCommand $command): void
    {
        // Mark the follow-up as answered — prevents duplicate re-evaluation
        // if the learner somehow submits twice.
        $this->evaluationResults->writeFollowUpPrompt(
            evaluationId:       $command->evaluationId,
            followUpPromptText: '', // text already stored — we're just updating status
            status:             'answered',
        );

        // Re-trigger evaluation. EvaluationService will read the original
        // submission and append the follow-up answer text as an additional
        // context layer in the prompt. If this still comes back uncertain,
        // the existing HumanReviewEntry stays in queue — no action needed here.
        $this->evaluationService->reEvaluateWithFollowUpAnswer(
            submissionId: $command->submissionId,
            learnerId:    $command->learnerId,
            sessionId:    $command->learnerSessionId,
            answerText:   $command->answerText,
        );
    }
}
