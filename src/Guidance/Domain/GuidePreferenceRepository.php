<?php
namespace Src\Guidance\Domain;

interface GuidePreferenceRepository
{
    public function forUser(string $userId): GuidePreference;

    public function dismissStep(string $userId, string $stepKey): void;

    public function setEnabled(string $userId, bool $enabled): void;

    public function resetDismissed(string $userId): void;
}
