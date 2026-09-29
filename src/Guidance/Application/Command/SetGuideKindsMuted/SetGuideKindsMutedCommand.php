<?php
namespace Src\Guidance\Application\Command\SetGuideKindsMuted;

final class SetGuideKindsMutedCommand
{
    /** @param string[] $mutedKinds GuideKind values to switch off */
    public function __construct(
        public readonly string $userId,
        public readonly array $mutedKinds,
    ) {}
}
