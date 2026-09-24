<?php
namespace Src\AIMediation\Application;

use Src\Simulation\Domain\Project\ProjectTemplate;

/**
 * Builds the prompt for Tiroco's one-way onboarding brief — a short,
 * in-character message voiced as a real project stakeholder, generated once
 * per project and cached (see GenerateOnboardingBriefingService). Same
 * systemPrompt()/userContent() shape as EvaluationPromptBuilder, but for
 * prose generation via AiTextGeneratorClient rather than structured
 * evaluation via AiEvaluatorClient.
 */
final class NarrativePromptBuilder
{
    public function systemPrompt(): string
    {
        return <<<'PROMPT'
            You are Tiroco, the narrative layer of IWSTGS, a professional software
            engineering training simulator. Your one job here is to write a short
            onboarding message, in character, from a specific person at the client
            company described below — the message a new engineer would actually
            receive on their first day.

            Hard rules, no exceptions:
            - Write in first person, as the named stakeholder. Sign off with their name.
            - This is a ONE-WAY message. Do not ask a question that expects or invites a
              reply. Do not write "let me know if..." or anything a reader would need to
              respond to. It is read and then the reader moves on.
            - Use only the facts given below. Do not invent names, numbers, deadlines, or
              details that are not present in the provided context. If something isn't
              given (e.g. no explicit deadline), do not make one up — omit it.
            - Do not break character. Do not mention that you are an AI, a simulation, or
              a training system. Do not mention Tiroco, IWSTGS, prompts, or evaluation.
            - Keep it to the length of a real, short onboarding email — a few short
              paragraphs at most, not an essay.
            - Plain prose only. No markdown, no headers, no bullet lists.

            Respond with ONLY the message text itself — no preamble, no explanation, no
            quotation marks around it.
            PROMPT;
    }

    /** @return array<int, array{type: string, text: string}> */
    public function userContent(ProjectTemplate $project): array
    {
        $sections = [];

        $stakeholders = $project->stakeholders();
        $author = $this->pickAuthor($stakeholders);
        $sections[] = $author !== null
            ? "## Write as\nName: {$author['name']}\nRole: {$author['role']}"
            : "## Write as\nNo specific person is on record — write as a generic Engineering Manager. Do not invent a name.";

        $sections[] = "## Company / Project\n{$project->businessContext()}";

        if ($stakeholders !== []) {
            $teamText = "## Other people on this project\n";
            foreach ($stakeholders as $s) {
                if ($author !== null && ($s['name'] ?? null) === $author['name']) {
                    continue;
                }
                $name = $s['name'] ?? null;
                $role = $s['role'] ?? 'team member';
                $concern = $s['concern'] ?? null;
                $teamText .= $name !== null
                    ? "- {$name}, {$role}" . ($concern ? " (cares most about: {$concern})" : '') . "\n"
                    : "- a {$role}" . ($concern ? " (cares most about: {$concern})" : '') . "\n";
            }
            $sections[] = $teamText;
        }

        $constraints = $project->overarchingConstraints();
        if ($constraints !== []) {
            $constraintsText = "## Constraints to be aware of\n";
            foreach ($constraints as $c) {
                $constraintsText .= '- ' . ($c['description'] ?? $c['type'] ?? '') . "\n";
            }
            $sections[] = $constraintsText;
        }

        $techContext = $project->techContext();
        if (! empty($techContext['existing_stack'])) {
            $sections[] = '## Tech stack\n' . implode(', ', $techContext['existing_stack']);
        }

        return [
            ['type' => 'text', 'text' => implode("\n\n", $sections)],
        ];
    }

    /** @param array $stakeholders @return array{name: string, role: string}|null */
    private function pickAuthor(array $stakeholders): ?array
    {
        $named = array_values(array_filter($stakeholders, fn ($s) => ! empty($s['name'])));
        if ($named === []) {
            return null;
        }

        $priorityRank = ['high' => 0, 'medium' => 1, 'low' => 2];
        usort($named, fn ($a, $b) => ($priorityRank[$a['priority'] ?? 'low'] ?? 2) <=> ($priorityRank[$b['priority'] ?? 'low'] ?? 2));

        return $named[0];
    }
}
