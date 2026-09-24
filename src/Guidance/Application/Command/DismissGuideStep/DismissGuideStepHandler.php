<?php
namespace Src\Guidance\Application\Command\DismissGuideStep;

use Src\Guidance\Domain\GuideCatalog;
use Src\Guidance\Domain\GuidePreferenceRepository;

final class DismissGuideStepHandler
{
    public function __construct(private readonly GuidePreferenceRepository $preferences) {}

    public function handle(DismissGuideStepCommand $command): void
    {
        if (GuideCatalog::find($command->stepKey) === null) {
            return; // unknown keys never reach storage
        }

        $this->preferences->dismissStep($command->userId, $command->stepKey);
    }
}
