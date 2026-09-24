<?php
namespace Src\AIMediation\Application;

use Src\EvalEngine\Domain\Evaluation\DimensionEvaluationSummary;
use Src\Simulation\Domain\Task\Task;

/**
 * Builds the prompt for Tiroco's incident ticket — the narrative that
 * introduces a consequence task. Per the user's explicit design goal: a real
 * engineer doesn't get handed a rubric printout when their code has a gap,
 * they get a downstream incident. This prompt is given the failing
 * dimension's rubric detail (criteriaMissed/evaluatorNotes) as INTERNAL
 * context only — the hard rule, repeated throughout the system prompt, is
 * that none of that detail is allowed to leak into the ticket text itself.
 * Same systemPrompt()/userContent() shape as NarrativePromptBuilder/
 * EvaluationPromptBuilder.
 */
final class IncidentTicketPromptBuilder
{
    public function systemPrompt(): string
    {
        return <<<'PROMPT'
            You are Tiroco, the narrative layer of IWSTGS, a professional software
            engineering training simulator. Your job here is to write a short incident
            ticket — the kind of report a real engineer would receive after their code
            shipped with a gap in it, days or weeks after the fact, from someone who has
            no idea what the underlying code problem is.

            You will be given internal notes describing what a learner's submission got
            wrong. These notes are for YOUR understanding only, so you can invent a
            REALISTIC, PLAUSIBLE downstream consequence of that kind of gap. You must
            never let the notes leak into the ticket itself.

            Hard rules, no exceptions:
            - Do NOT name, describe, or hint at the specific missing check, validation,
              field, edge case, or logic. The reader of the ticket does not get a
              diagnosis — they get a symptom, exactly like a real bug report.
            - Do NOT use language like "should have validated," "the fix is," "missing
              check for," "you forgot to," or anything that names the cause. Describe
              only what a stakeholder, a user, or a monitoring system would observe from
              the OUTSIDE.
            - Write it as a ticket: something reported by a specific role (a nurse, a
              manager, an on-call engineer, a monitoring alert) — factual, specific
              about what was observed, not diagnostic about why.
            - This is a ONE-WAY report, not a conversation. Do not ask a question that
              expects or invites a reply.
            - Do not break character. Do not mention that you are an AI, a simulation,
              a training system, evaluation, rubrics, or criteria.
            - Keep it to the length of a real, short ticket — a few sentences, not an
              essay.
            - Plain prose only. No markdown, no headers, no bullet lists.

            Respond with ONLY the ticket text itself — no preamble, no explanation, no
            quotation marks around it.
            PROMPT;
    }

    /** @param DimensionEvaluationSummary[] $failingDimensions @return array<int, array{type: string, text: string}> */
    public function userContent(Task $consequenceTask, array $failingDimensions): array
    {
        $taskPrimitives = $consequenceTask->toPrimitives();

        $sections = [];
        $sections[] = "## Scenario context (for tone/grounding only — do not quote directly)\n{$taskPrimitives['task_brief']}";

        $internalText = "## Internal notes on what went wrong — NEVER reveal this, use only to invent a plausible symptom\n";
        foreach ($failingDimensions as $dimension) {
            $internalText .= "- Area: {$dimension->taskDimensionLabel}\n";
            if ($dimension->evaluatorNotes) {
                $internalText .= "  Notes: {$dimension->evaluatorNotes}\n";
            }
            if ($dimension->criteriaMissed !== []) {
                $internalText .= '  Missed: ' . implode('; ', $dimension->criteriaMissed) . "\n";
            }
        }
        $sections[] = $internalText;

        return [
            ['type' => 'text', 'text' => implode("\n\n", $sections)],
        ];
    }
}
