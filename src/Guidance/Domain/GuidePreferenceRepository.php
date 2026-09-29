<?php
namespace Src\Guidance\Domain;

use Src\Guidance\Domain\Message\GuideKind;

interface GuidePreferenceRepository
{
    public function forUser(string $userId): GuidePreference;

    public function dismissStep(string $userId, string $stepKey): void;

    public function setEnabled(string $userId, bool $enabled): void;

    public function resetDismissed(string $userId): void;

    /** @param string[] $mutedKinds */
    public function setMutedKinds(string $userId, array $mutedKinds): void;

    public function pauseKind(string $userId, GuideKind $kind, \DateTimeInterface $until): void;

    /** Records activity now and returns the previous activity time (null on the first visit). */
    public function touchActive(string $userId): ?string;
}
