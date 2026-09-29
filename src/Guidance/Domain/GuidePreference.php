<?php
namespace Src\Guidance\Domain;

use Src\Guidance\Domain\Message\GuideKind;

final class GuidePreference
{
    /**
     * @param string[] $dismissedSteps walkthrough steps already seen
     * @param string[] $mutedKinds GuideKind values the learner switched off
     * @param array<string, string> $pausedUntil GuideKind value => ISO time; set by back-off, never shown to the learner
     */
    public function __construct(
        public readonly bool $isEnabled,
        public readonly array $dismissedSteps,
        public readonly array $mutedKinds = [],
        public readonly array $pausedUntil = [],
        public readonly ?string $lastActiveAt = null,
    ) {}

    public static function default(): self
    {
        return new self(true, []);
    }

    public function hasDismissed(string $stepKey): bool
    {
        return in_array($stepKey, $this->dismissedSteps, true);
    }

    public function isMuted(GuideKind $kind): bool
    {
        return in_array($kind->value, $this->mutedKinds, true);
    }

    /**
     * May Tiroco pop up a message of this kind? Turning the guide off stops
     * every pop-up; announcements stay readable on the What's new page.
     */
    public function allowsPopUp(GuideKind $kind, \DateTimeInterface $now): bool
    {
        if (! $this->isEnabled || $this->isMuted($kind)) {
            return false;
        }

        $paused = $this->pausedUntil[$kind->value] ?? null;

        return $paused === null || new \DateTimeImmutable($paused) <= $now;
    }
}
