<?php
namespace Src\Guidance\Application\Listener;

use Illuminate\Support\Facades\Log;
use Src\EvalEngine\Domain\Evaluation\EvaluationComplete;
use Src\Guidance\Application\Service\EvaluationGuideTriggers;

/**
 * Registered after PostEvaluationRouter (module providers boot in order), so
 * rank changes and injected cards from this evaluation already exist. The
 * guide is a nice-to-have: nothing it does may ever break an evaluation.
 */
final class QueueGuideMessagesOnEvaluation
{
    public function __construct(private readonly EvaluationGuideTriggers $triggers) {}

    public function handle(EvaluationComplete $event): void
    {
        try {
            $this->triggers->observe($event);
        } catch (\Throwable $e) {
            Log::warning("Guide triggers failed for evaluation {$event->evaluationId}: {$e->getMessage()}");
        }
    }
}
