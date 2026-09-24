<?php
namespace Src\Guidance\Application\Command\ResetGuide;

final class ResetGuideCommand
{
    public function __construct(
        public readonly string $userId,
    ) {}
}
