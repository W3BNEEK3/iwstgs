<?php
namespace Src\Guidance\Application\Command\DismissGuideStep;

final class DismissGuideStepCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $stepKey,
    ) {}
}
