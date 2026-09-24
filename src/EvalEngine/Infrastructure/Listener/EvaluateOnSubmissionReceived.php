<?php
namespace Src\EvalEngine\Infrastructure\Listener;

use Illuminate\Support\Facades\Log;
use Src\EvalEngine\Application\Service\EvaluationService;
use Src\Shared\Infrastructure\Feature\FeatureFlagService;
use Src\Submission\Domain\Submission\SubmissionReceived;

/**
 * A failed or unavailable evaluation must never fail the learner's own
 * submission — the submit request already succeeded and returned a
 * response by the time this listener runs. If Claude errors (missing key,
 * API failure, bad response), it's logged and swallowed here; the
 * submission simply stays ungraded rather than the request 500ing.
 */
final class EvaluateOnSubmissionReceived
{
    public function __construct(
        private readonly FeatureFlagService $flags,
        private readonly EvaluationService $evaluationService,
    ) {}

    public function handle(SubmissionReceived $event): void
    {
        if (! $this->flags->isEnabled('aimediation.claude_evaluation')) {
            return;
        }

        try {
            $this->evaluationService->evaluate(
                submissionId:      $event->submissionId,
                learnerId:         $event->learnerId,
                learnerSessionId:  $event->learnerSessionId,
                taskId:            $event->taskId,
            );
        } catch (\Throwable $e) {
            Log::error("Evaluation failed for submission {$event->submissionId}: {$e->getMessage()}", [
                'exception' => $e,
            ]);
        }
    }
}
