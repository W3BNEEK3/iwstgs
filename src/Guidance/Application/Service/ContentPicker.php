<?php
namespace Src\Guidance\Application\Service;

use Src\Guidance\Domain\Content\GuideContentRepository;
use Src\Guidance\Domain\Content\GuideResource;
use Src\Guidance\Domain\Content\GuideTip;
use Src\Guidance\Domain\Message\GuideMessageRepository;

/**
 * Chooses which tip or outside resource Tiroco offers. Deliberately
 * deterministic: the AI writer only phrases the message around the item
 * chosen here, so it can never invent a tip or a link.
 */
final class ContentPicker
{
    /** A tip may come round again after this long. */
    private const TIP_REPEAT_DAYS = 60;

    private const LEVEL_FOR_TIER = ['Junior' => 'beginner', 'Mid' => 'intermediate', 'Senior' => 'advanced'];

    public function __construct(
        private readonly GuideContentRepository $content,
        private readonly GuideMessageRepository $messages,
    ) {}

    public function tipFor(string $userId, ?string $page): ?GuideTip
    {
        $recent = $this->messages->usedIds($userId, 'tip_id', now()->subDays(self::TIP_REPEAT_DAYS));

        foreach ($this->content->tips(activeOnly: true) as $tip) {
            if ($tip->suitsPage($page) && ! in_array($tip->id, $recent, true)) {
                return $tip;
            }
        }

        return null;
    }

    /**
     * The best unused resource for a skill: never one already recommended or
     * that the learner opted out of; the learner's level first, then the
     * nearest level.
     */
    public function resourceFor(string $userId, string $dimensionId, ?string $rankTier): ?GuideResource
    {
        $excluded = [...$this->messages->usedIds($userId, 'resource_id'), ...$this->content->optedOutResourceIds($userId)];
        $candidates = array_values(array_filter(
            $this->content->resources(activeOnly: true),
            fn (GuideResource $r) => in_array($dimensionId, $r->dimensions, true)
                && ! in_array($r->id, $excluded, true)
                && $r->lastCheckOk !== false,
        ));

        if ($candidates === []) {
            return null;
        }

        $levels = GuideResource::LEVELS;
        $target = array_search(self::LEVEL_FOR_TIER[$rankTier] ?? 'beginner', $levels, true);
        usort($candidates, fn (GuideResource $a, GuideResource $b) =>
            [abs(array_search($a->level, $levels, true) - $target), $a->name]
            <=> [abs(array_search($b->level, $levels, true) - $target), $b->name]);

        return $candidates[0];
    }
}
