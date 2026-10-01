<?php
namespace Src\Guidance\Application\Query\GetGuideHealth;

use Src\Guidance\Domain\Message\GuideMessage;

final class GuideHealthView
{
    /**
     * @param array<int, array{key: string, label: string, kind: string, enabled: bool, cooldown_hours: int,
     *   shown: int, helpful: int, not_helpful: int, quick_dismiss: int}> $triggers
     * @param GuideMessage[] $recent
     */
    public function __construct(
        public readonly int $days,
        public readonly int $dailyCap,
        public readonly array $triggers,
        public readonly array $recent,
    ) {}
}
