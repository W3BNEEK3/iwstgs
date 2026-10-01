<?php
namespace Src\Guidance\Application\Command\UpdateGuideSettings;

final class UpdateGuideSettingsCommand
{
    /** @param array<string, array{enabled: bool, cooldown_hours: int}> $triggers */
    public function __construct(
        public readonly int $dailyCap,
        public readonly array $triggers,
    ) {}
}
