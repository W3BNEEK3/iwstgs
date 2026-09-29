<?php
namespace Src\Guidance\Application\Query\ListGuideContent;

use Src\Guidance\Domain\Content\GuideContentRepository;

final class ListGuideContentHandler
{
    public function __construct(private readonly GuideContentRepository $content) {}

    public function handle(ListGuideContentQuery $query): array
    {
        return match ($query->type) {
            'tips'          => $this->content->tips(activeOnly: false),
            'resources'     => $this->content->resources(activeOnly: false),
            'announcements' => $this->content->announcements(),
            default         => throw new \InvalidArgumentException("Unknown guide content type: {$query->type}"),
        };
    }
}
