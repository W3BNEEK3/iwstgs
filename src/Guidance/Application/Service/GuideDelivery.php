<?php
namespace Src\Guidance\Application\Service;

use Src\Guidance\Domain\GuidePreferenceRepository;
use Src\Guidance\Domain\Message\GuideMessage;
use Src\Guidance\Domain\Message\GuideMessageRepository;
use Src\Guidance\Domain\Message\TriggerCatalog;

/**
 * Picks the one message (if any) Tiroco shows on this page load, applying
 * the frequency rules in design doc v2-05 §3.2: one per page, a daily cap on
 * unsolicited kinds, priority order, quiet zones, and the learner's
 * switches. The chosen message is written (AI or fallback) and marked shown.
 */
final class GuideDelivery
{
    private const STALE_AFTER_HOURS = 48;

    /** During the check-in only a genuinely-stuck nudge may interrupt. */
    private const QUIET_PAGES = ['diagnostic' => [TriggerCatalog::STUCK_IDLE], 'enrol' => []];

    public function __construct(
        private readonly GuideMessageRepository $messages,
        private readonly GuidePreferenceRepository $preferences,
        private readonly GuideSettings $settings,
        private readonly GuideMessageWriter $writer,
    ) {}

    public function next(string $userId, ?string $page): ?GuideMessage
    {
        $this->messages->expireOlderThan(now()->subHours(self::STALE_AFTER_HOURS));

        $preference = $this->preferences->forUser($userId);
        $capReached = $this->messages->countShownSince($userId, now()->subDay(), cappedKindsOnly: true) >= $this->settings->dailyCap();

        $candidates = array_filter($this->messages->deliverable($userId), function (GuideMessage $m) use ($preference, $capReached, $page) {
            if (! $preference->allowsPopUp($m->kind, now())) {
                return false;
            }
            if ($capReached && $m->kind->isCapped()) {
                return false;
            }
            if ($page !== null && array_key_exists($page, self::QUIET_PAGES)) {
                return in_array($m->triggerKey, self::QUIET_PAGES[$page], true);
            }

            return true;
        });

        if ($candidates === []) {
            return null;
        }

        // Most urgent kind first; within a kind the newest (most relevant now), then catalogue order.
        usort($candidates, fn (GuideMessage $a, GuideMessage $b) =>
            [$a->kind->priority(), $b->createdAt, TriggerCatalog::rank($a->triggerKey)]
            <=> [$b->kind->priority(), $a->createdAt, TriggerCatalog::rank($b->triggerKey)]);

        $message = $this->writer->write($candidates[0]);
        $this->messages->markShown($message->id);

        return $message;
    }
}
