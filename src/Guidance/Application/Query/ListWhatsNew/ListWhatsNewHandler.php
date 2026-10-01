<?php
namespace Src\Guidance\Application\Query\ListWhatsNew;

use Src\Guidance\Application\Service\VisitGuideTriggers;
use Src\Guidance\Domain\Content\FeatureAnnouncement;
use Src\Guidance\Domain\Content\GuideContentRepository;

/** Every live learner announcement, newest first — readable even with Tiroco switched off. */
final class ListWhatsNewHandler
{
    public function __construct(
        private readonly GuideContentRepository $content,
        private readonly VisitGuideTriggers $visitTriggers,
    ) {}

    /** @return FeatureAnnouncement[] */
    public function handle(ListWhatsNewQuery $query): array
    {
        return array_values(array_filter(
            $this->content->publishedAnnouncements(),
            fn (FeatureAnnouncement $a) => $this->visitTriggers->isLive($a),
        ));
    }
}
