<?php
namespace Src\AIMediation\Application\Service;

use Illuminate\Support\Facades\Log;
use Src\AIMediation\Application\NarrativePromptBuilder;
use Src\AIMediation\Domain\Provider\AiTextGeneratorClient;
use Src\Shared\Application\Bus\CommandBus;
use Src\Simulation\Application\Command\RecordOnboardingBriefing\RecordOnboardingBriefingCommand;
use Src\Simulation\Domain\Project\ProjectTemplate;

/**
 * Tiroco's one-way onboarding brief (per the "Controlled Generative
 * Privilege" pattern in BLD §8.3): generated once per project, on whichever
 * learner's request happens to hit it first, then cached on the project
 * forever after — every subsequent learner reads the same cached text, no
 * re-generation, same shape as EvaluationService ("call the AI once on
 * trigger, persist, never re-call to redisplay").
 *
 * Persists via CommandBus rather than injecting Simulation's
 * ProjectTemplateRepository directly — AIMediation never holds another
 * module's repository, same convention EvaluationPromptBuilder already
 * follows for its own Simulation reads.
 *
 * Deliberately doesn't log an AimediationEvent — that audit log's schema
 * (learner_id + session_id required, a fixed trigger/action enum vocabulary)
 * is built for per-learner adaptive-engine events tied to a submission; this
 * is project-level content generated once on behalf of every future
 * learner, not a decision about any one learner's evaluation.
 */
final class GenerateOnboardingBriefingService
{
    public function __construct(
        private readonly AiTextGeneratorClient $generator,
        private readonly NarrativePromptBuilder $promptBuilder,
        private readonly CommandBus $commandBus,
    ) {}

    public function ensureGenerated(ProjectTemplate $project): string
    {
        $existing = $project->onboardingBriefing();
        if ($existing !== null) {
            return $existing;
        }

        try {
            $text = $this->generator->generate(
                $this->promptBuilder->systemPrompt(),
                $this->promptBuilder->userContent($project),
            );
        } catch (\Throwable $e) {
            // An unconfigured or failing AI provider must never block a learner
            // from starting a project. The fallback is not cached, so the next
            // request retries generation.
            Log::warning("Onboarding briefing generation failed for project {$project->id()}: {$e->getMessage()}");

            return $this->fallbackBriefing($project);
        }

        $this->commandBus->dispatch(new RecordOnboardingBriefingCommand($project->id(), $text));

        return $text;
    }

    private function fallbackBriefing(ProjectTemplate $project): string
    {
        $lines = ["Welcome to the {$project->title()} team.", '', $project->businessContext()];

        $stakeholders = array_filter(array_map(
            fn ($s) => is_array($s) && isset($s['name']) ? "{$s['name']}" . (isset($s['role']) ? " ({$s['role']})" : '') : null,
            $project->stakeholders(),
        ));
        if ($stakeholders !== []) {
            $lines[] = '';
            $lines[] = 'People you will hear from: ' . implode(', ', $stakeholders) . '.';
        }

        $lines[] = '';
        $lines[] = 'Read the scenario brief and reference materials carefully, then plan your first sprint.';

        return implode("\n", $lines);
    }
}
