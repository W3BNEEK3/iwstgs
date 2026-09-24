<?php
namespace Src\AIMediation\Application\Service;

use Illuminate\Support\Facades\Log;
use Src\AIMediation\Application\IncidentTicketPromptBuilder;
use Src\AIMediation\Domain\Provider\AiTextGeneratorClient;
use Src\EvalEngine\Domain\Evaluation\DimensionEvaluationSummary;
use Src\Simulation\Domain\Task\Task;

/**
 * Unlike GenerateOnboardingBriefingService, there is nothing to cache here —
 * every injection is a unique event tied to one specific submission's
 * specific failure, so this always generates fresh.
 */
final class GenerateIncidentTicketService
{
    public function __construct(
        private readonly AiTextGeneratorClient $generator,
        private readonly IncidentTicketPromptBuilder $promptBuilder,
    ) {}

    /** @param DimensionEvaluationSummary[] $failingDimensions */
    public function generate(Task $consequenceTask, array $failingDimensions): string
    {
        try {
            return $this->generator->generate(
                $this->promptBuilder->systemPrompt(),
                $this->promptBuilder->userContent($consequenceTask, $failingDimensions),
            );
        } catch (\Throwable $e) {
            // Without this, a provider outage would abort the whole post-evaluation
            // routing and the consequence card would silently never appear.
            Log::warning("Incident ticket generation failed for task {$consequenceTask->id()}: {$e->getMessage()}");

            $p = $consequenceTask->toPrimitives();

            return "INCIDENT: A problem has been reported that traces back to your recent work.\n\n"
                . "Follow-up required: {$p['title']}\n\n{$p['task_brief']}";
        }
    }
}
