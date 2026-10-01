<?php
namespace Src\Guidance\Application\Service;

use Src\Guidance\Domain\Message\GuideMessage;
use Src\Guidance\Domain\Message\TriggerCatalog;

/** The prompt Tiroco's writer uses (design doc v2-05 §8.3). */
final class GuidePromptBuilder
{
    /** What each trigger's message should achieve — the writer's brief. */
    private const PURPOSE = [
        TriggerCatalog::REPEAT_FAIL => 'The learner failed the same task twice. Name, in plain words, the one missed point that matters most and suggest one concrete way to approach it (re-reading the brief or materials, listing edge cases, checking deliverables). Encourage them. Never give the answer.',
        TriggerCatalog::STUCK_IDLE => 'The learner has been on a task for a while without submitting. Gently check in; suggest the hints and reference materials on the page, or breaking the task into a first small step; remind them a partial attempt with a clear explanation still earns useful feedback.',
        TriggerCatalog::THIN_EXPLANATIONS => 'The learner writes very short explanations and communication keeps scoring low. Show them a simple three-part structure (what I did, why, how I checked it) and why reviewers need it.',
        TriggerCatalog::RANK_CHANGE => 'The learner\'s rank changed. If up: congratulate them specifically and say tasks will give less guidance and more complexity. If down: reassure them it is support, not a penalty, and that tasks will give more guidance.',
        TriggerCatalog::WELCOME_BACK => 'The learner is back after some days away. Welcome them warmly, say where they were (if known) and suggest one easy first step to get back into it.',
        TriggerCatalog::WEAK_DIMENSION => 'One skill keeps scoring below proficient. Recommend the given outside resource as a companion to their work here: say in one sentence why it fits this skill. Use its name exactly; do not include a link (the card has a button).',
        TriggerCatalog::QUIET_MOMENT => 'Share the given tip about using the platform well. Keep its meaning exactly; you may rephrase it to fit where the learner is. One or two sentences.',
    ];

    public function systemPrompt(): string
    {
        return <<<'PROMPT'
            You are Tiroco, the guide inside Areyna, a platform where junior developers
            learn by doing realistic project work with a fictional team. You speak like a
            kind senior colleague: warm, plain English, specific, never patronising.

            You are given one situation and facts about the learner. Write ONE short
            message that helps with exactly that situation.

            Rules:
            - Title: at most 8 words. Body: at most 60 words, plain text, second person.
            - Never give the solution, code, or step-by-step answers for the task they are
              working on. Point them to the brief, hints and reference materials instead.
            - Never grade them or predict grades.
            - Never mention internal terms (CAC, gap types, dimension ids, triggers).
            - Use only the facts provided. Never invent facts, names, numbers or links.
            - No markdown, no lists, no code, no URLs, no emojis.
            - If the facts are not enough to say something genuinely useful, skip.

            Reply with JSON only, no other text:
            {"title": "...", "body": "..."}   or   {"skip": true}
            PROMPT;
    }

    /**
     * @param array<string, mixed> $snapshot learner facts from LearnerSnapshotBuilder
     * @param GuideMessage[] $recent messages Tiroco sent lately, newest first
     */
    public function userContent(GuideMessage $message, array $snapshot, array $recent): string
    {
        $lines = [
            'Situation: ' . (self::PURPOSE[$message->triggerKey] ?? TriggerCatalog::find($message->triggerKey)['label']),
            'Facts about this situation: ' . json_encode($message->facts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'About the learner: ' . json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'A draft you may improve on or keep: ' . json_encode(['title' => $message->title, 'body' => $message->body], JSON_UNESCAPED_UNICODE),
        ];

        if ($recent !== []) {
            $lines[] = 'Messages you sent this learner recently (do not repeat them): '
                . json_encode(array_map(fn (GuideMessage $m) => $m->title . ' — ' . $m->body, $recent), JSON_UNESCAPED_UNICODE);
        }

        return implode("\n\n", $lines);
    }

    public function hasPurpose(string $triggerKey): bool
    {
        return isset(self::PURPOSE[$triggerKey]);
    }
}
