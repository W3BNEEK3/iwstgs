<?php
namespace Src\Guidance\Application\Service;

use Src\Guidance\Domain\GuidePreferenceRepository;
use Src\Guidance\Domain\Message\GuideKind;
use Src\Guidance\Domain\Message\GuideMessageRepository;
use Src\Guidance\Domain\Message\NewGuideMessage;
use Src\Shared\Infrastructure\Feature\FeatureFlagService;

/**
 * The one door every trigger goes through. Cheap checks only (no AI here):
 * the feature flag, the admin's trigger switch, the learner's own switches
 * and back-off pauses, and the trigger's cooldown. What passes is stored as
 * a pending message with its authored fallback text; the writer may rewrite
 * it at delivery time.
 */
final class GuideMessageQueue
{
    public function __construct(
        private readonly GuideMessageRepository $messages,
        private readonly GuidePreferenceRepository $preferences,
        private readonly GuideSettings $settings,
        private readonly FeatureFlagService $flags,
    ) {}

    public function queue(NewGuideMessage $message): ?string
    {
        $kind = $message->kind();
        $flag = $kind === GuideKind::Announcement ? 'guide.announcements' : 'guide.ai_nudges';

        if (! $this->flags->isEnabled($flag) || ! $this->settings->isTriggerEnabled($message->triggerKey)) {
            return null;
        }

        if (! $this->preferences->forUser($message->userId)->allowsPopUp($kind, now())) {
            return null;
        }

        // Cooldown 0 means "once per context, ever" (a rank event, an announcement).
        $hours = $this->settings->cooldownHours($message->triggerKey);
        $since = $hours === 0 ? new \DateTimeImmutable('@0') : now()->subHours($hours);
        if ($this->messages->existsSince($message->userId, $message->triggerKey, $message->contextRef, $since)) {
            return null;
        }

        return $this->messages->queue($message, $message->needsWriting ? 'pending' : 'ready');
    }
}
