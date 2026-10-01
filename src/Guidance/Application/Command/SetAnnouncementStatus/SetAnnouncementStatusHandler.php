<?php
namespace Src\Guidance\Application\Command\SetAnnouncementStatus;

use Src\Guidance\Domain\Content\AnnouncementNotReady;
use Src\Guidance\Domain\Content\GuideContentRepository;

final class SetAnnouncementStatusHandler
{
    public function __construct(private readonly GuideContentRepository $content) {}

    public function handle(SetAnnouncementStatusCommand $command): void
    {
        $announcement = $this->content->findAnnouncement($command->announcementId);
        if ($announcement === null || ! in_array($command->status, ['draft', 'published', 'archived'], true)) {
            return;
        }

        // Nothing reaches learners without an approved, learner-facing title and body.
        if ($command->status === 'published' && (trim((string) $announcement->title) === '' || trim((string) $announcement->body) === '')) {
            throw new AnnouncementNotReady('Add the learner-facing title and text (or generate them) before publishing.');
        }

        $this->content->setAnnouncementStatus($announcement->id, $command->status);
    }
}
