<?php
namespace Src\Guidance\Application\Query\GetGuideContentItem;

use Src\Guidance\Domain\Content\GuideContentRepository;

final class GetGuideContentItemHandler
{
    public function __construct(private readonly GuideContentRepository $content) {}

    public function handle(GetGuideContentItemQuery $query): ?object
    {
        return match ($query->type) {
            'tip'          => $this->content->findTip($query->id),
            'resource'     => $this->content->findResource($query->id),
            'announcement' => $this->content->findAnnouncement($query->id),
            default        => null,
        };
    }
}
