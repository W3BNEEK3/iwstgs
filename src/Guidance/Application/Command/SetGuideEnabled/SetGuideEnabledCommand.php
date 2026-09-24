<?php
namespace Src\Guidance\Application\Command\SetGuideEnabled;

final class SetGuideEnabledCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly bool $enabled,
    ) {}
}
