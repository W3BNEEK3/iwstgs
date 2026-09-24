<?php
namespace Src\Guidance\Application\Command\ResetGuide;

use Src\Guidance\Domain\GuidePreferenceRepository;

final class ResetGuideHandler
{
    public function __construct(private readonly GuidePreferenceRepository $preferences) {}

    public function handle(ResetGuideCommand $command): void
    {
        $this->preferences->resetDismissed($command->userId);
    }
}
