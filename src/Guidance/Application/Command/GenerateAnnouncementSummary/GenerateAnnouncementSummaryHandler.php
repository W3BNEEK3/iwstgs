<?php
namespace Src\Guidance\Application\Command\GenerateAnnouncementSummary;

use Src\AIMediation\Domain\Provider\AiTextGeneratorClient;
use Src\Guidance\Application\Service\GuideMessageValidator;
use Src\Guidance\Domain\Content\AnnouncementNotReady;
use Src\Guidance\Domain\Content\GuideContentRepository;

/**
 * Admin clicks "Generate learner summary": the AI turns the admin's rough
 * notes into a short learner-facing title and text, saved on the draft for
 * the admin to edit and approve. Never published automatically.
 */
final class GenerateAnnouncementSummaryHandler
{
    public const MAX_BODY_CHARS = 400;

    public function __construct(
        private readonly AiTextGeneratorClient $generator,
        private readonly GuideMessageValidator $validator,
        private readonly GuideContentRepository $content,
    ) {}

    public function handle(GenerateAnnouncementSummaryCommand $command): void
    {
        $announcement = $this->content->findAnnouncement($command->announcementId);
        if ($announcement === null) {
            return;
        }

        try {
            $raw = $this->generator->generate($this->systemPrompt(), [[
                'type' => 'text',
                'text' => "Internal title: {$announcement->internalTitle}\n\nNotes from the team:\n{$announcement->notes}",
            ]]);
        } catch (\Throwable $e) {
            throw new AnnouncementNotReady('The AI provider could not write a summary right now (' . $e->getMessage() . '). You can write the title and text yourself.');
        }

        $written = $this->validator->parse($raw);
        if ($written === null || mb_strlen($written['body']) > self::MAX_BODY_CHARS) {
            throw new AnnouncementNotReady('The generated summary did not meet the length and style rules. Try again, add detail to the notes, or write it yourself.');
        }

        $this->content->saveAnnouncement($announcement->id, [
            'internal_title' => $announcement->internalTitle,
            'notes'          => $announcement->notes,
            'title'          => $written['title'],
            'body'           => $written['body'],
            'link_url'       => $announcement->linkUrl,
            'feature_flag'   => $announcement->featureFlag,
        ], null);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
            You write short "what's new" announcements for learners on Areyna, a platform
            where junior developers learn by doing realistic project work. You are given the
            team's internal notes about a change. Write the announcement a learner will see.

            Rules:
            - Title: at most 8 words, starting with what they can now do.
            - Body: at most 40 words. Say what changed and why it helps the learner.
            - Plain, friendly English for a developer in their first year. No internal names,
              ticket numbers, technical implementation details, markdown, code, links or emojis.
            - Only describe what the notes say. If the notes describe nothing a learner would
              notice, reply {"skip": true}.

            Reply with JSON only: {"title": "...", "body": "..."}
            PROMPT;
    }
}
