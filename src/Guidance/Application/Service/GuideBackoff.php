<?php
namespace Src\Guidance\Application\Service;

use Src\Guidance\Domain\GuidePreferenceRepository;
use Src\Guidance\Domain\Message\GuideKind;
use Src\Guidance\Domain\Message\GuideMessage;
use Src\Guidance\Domain\Message\GuideMessageRepository;

/**
 * Tiroco gets quieter, silently, when a learner keeps waving a kind of
 * message away: three closed unread in a row, or two rated "not helpful" in
 * a row, pause that kind for a week. The learner's own switches are
 * separate and never changed by this.
 */
final class GuideBackoff
{
    private const PAUSE_DAYS = 7;

    public function __construct(
        private readonly GuideMessageRepository $messages,
        private readonly GuidePreferenceRepository $preferences,
    ) {}

    public function afterFeedback(string $userId, GuideKind $kind): void
    {
        if ($kind === GuideKind::Announcement) {
            return;
        }

        $recent = $this->messages->recentShownOfKind($userId, $kind, 3);

        $unread = count($recent) === 3 && array_filter($recent, fn (GuideMessage $m) => ! $m->wasDismissedUnread()) === [];
        $rated = array_slice(array_values(array_filter($recent, fn (GuideMessage $m) => $m->rating !== null)), 0, 2);
        $unhelpful = count($rated) === 2 && $rated[0]->rating === 'not_helpful' && $rated[1]->rating === 'not_helpful';

        if ($unread || $unhelpful) {
            $this->preferences->pauseKind($userId, $kind, now()->addDays(self::PAUSE_DAYS));
        }
    }
}
