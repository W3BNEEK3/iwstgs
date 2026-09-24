<?php
namespace Src\Guidance\Domain;

final class GuidePreference
{
    /** @param string[] $dismissedSteps */
    public function __construct(
        public readonly bool $isEnabled,
        public readonly array $dismissedSteps,
    ) {}

    public static function default(): self
    {
        return new self(true, []);
    }

    public function hasDismissed(string $stepKey): bool
    {
        return in_array($stepKey, $this->dismissedSteps, true);
    }
}
