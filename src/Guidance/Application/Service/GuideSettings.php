<?php
namespace Src\Guidance\Application\Service;

use Src\Guidance\Domain\Message\TriggerCatalog;
use Src\Shared\Infrastructure\Settings\PlatformSettingsService;

/**
 * Admin-tunable knobs for Tiroco, stored as platform settings with the
 * catalogue's values as defaults:
 *   guide.daily_cap  — unsolicited messages per learner per 24 hours
 *   guide.triggers   — {trigger_key: {enabled: bool, cooldown_hours: int}}
 */
final class GuideSettings
{
    public const DEFAULT_DAILY_CAP = 3;

    public function __construct(private readonly PlatformSettingsService $settings) {}

    public function dailyCap(): int
    {
        return max(0, (int) $this->settings->get('guide.daily_cap', self::DEFAULT_DAILY_CAP));
    }

    public function isTriggerEnabled(string $key): bool
    {
        return (bool) ($this->overrides()[$key]['enabled'] ?? true);
    }

    public function cooldownHours(string $key): int
    {
        return max(0, (int) ($this->overrides()[$key]['cooldown_hours'] ?? TriggerCatalog::find($key)['cooldown_hours'] ?? 24));
    }

    /** @param array<string, array{enabled: bool, cooldown_hours: int}> $triggers */
    public function save(int $dailyCap, array $triggers): void
    {
        $this->settings->set('guide.daily_cap', max(0, min(20, $dailyCap)));
        $this->settings->set('guide.triggers', array_intersect_key($triggers, TriggerCatalog::all()));
    }

    /** @return array<string, array{enabled?: bool, cooldown_hours?: int}> */
    private function overrides(): array
    {
        $value = $this->settings->get('guide.triggers', []);

        return is_array($value) ? $value : [];
    }
}
