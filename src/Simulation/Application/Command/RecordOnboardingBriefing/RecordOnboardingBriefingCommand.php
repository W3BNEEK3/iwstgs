<?php
namespace Src\Simulation\Application\Command\RecordOnboardingBriefing;

final class RecordOnboardingBriefingCommand
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $text,
    ) {}
}
