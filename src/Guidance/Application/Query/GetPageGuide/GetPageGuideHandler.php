<?php
namespace Src\Guidance\Application\Query\GetPageGuide;

use Src\Guidance\Domain\GuideCatalog;
use Src\Guidance\Domain\GuidePreferenceRepository;

final class GetPageGuideHandler
{
    public function __construct(private readonly GuidePreferenceRepository $preferences) {}

    public function handle(GetPageGuideQuery $query): ?PageGuideView
    {
        $steps = array_values(array_filter(
            GuideCatalog::steps(),
            fn (array $s) => $s['page'] === $query->page && ($s['when'] === null || ! empty($query->context[$s['when']])),
        ));

        if ($steps === []) {
            return null;
        }

        // A milestone step is the more timely message, so it goes first.
        usort($steps, fn (array $a, array $b) => ($b['when'] !== null) <=> ($a['when'] !== null));

        $preference = $this->preferences->forUser($query->userId);
        $autoShow = $preference->isEnabled
            ? array_values(array_filter(array_column($steps, 'key'), fn (string $k) => ! $preference->hasDismissed($k)))
            : [];

        return new PageGuideView(
            isEnabled:    $preference->isEnabled,
            steps:        array_map(fn (array $s) => ['key' => $s['key'], 'title' => $s['title'], 'cards' => $s['cards']], $steps),
            autoShowKeys: $autoShow,
        );
    }
}
