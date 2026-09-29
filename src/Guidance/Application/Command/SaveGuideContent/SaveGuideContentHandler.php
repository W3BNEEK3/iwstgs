<?php
namespace Src\Guidance\Application\Command\SaveGuideContent;

use Src\Guidance\Domain\Content\GuideContentRepository;

final class SaveGuideContentHandler
{
    public function __construct(private readonly GuideContentRepository $content) {}

    public function handle(SaveGuideContentCommand $command): void
    {
        match ($command->type) {
            'tip'          => $this->content->saveTip($command->id, $command->data),
            'resource'     => $this->content->saveResource($command->id, $command->data),
            'announcement' => $this->content->saveAnnouncement($command->id, $command->data, $command->actorUserId),
            default        => throw new \InvalidArgumentException("Unknown guide content type: {$command->type}"),
        };
    }
}
