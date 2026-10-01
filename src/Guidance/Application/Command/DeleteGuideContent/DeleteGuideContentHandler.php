<?php
namespace Src\Guidance\Application\Command\DeleteGuideContent;

use Src\Guidance\Domain\Content\GuideContentRepository;

/** Announcements are archived, never deleted, so learners' What's new history stays intact. */
final class DeleteGuideContentHandler
{
    public function __construct(private readonly GuideContentRepository $content) {}

    public function handle(DeleteGuideContentCommand $command): void
    {
        match ($command->type) {
            'tip'      => $this->content->deleteTip($command->id),
            'resource' => $this->content->deleteResource($command->id),
            default    => throw new \InvalidArgumentException("Guide content type {$command->type} can't be deleted"),
        };
    }
}
