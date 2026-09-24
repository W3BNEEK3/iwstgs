<?php
namespace Src\AIMediation\Application\Service;

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
        return $this->generator->generate(
            $this->promptBuilder->systemPrompt(),
            $this->promptBuilder->userContent($consequenceTask, $failingDimensions),
        );
    }
}
