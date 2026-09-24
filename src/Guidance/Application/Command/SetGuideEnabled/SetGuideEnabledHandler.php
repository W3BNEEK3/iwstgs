<?php
namespace Src\Guidance\Application\Command\SetGuideEnabled;

use Src\Guidance\Domain\GuidePreferenceRepository;

final class SetGuideEnabledHandler
{
    public function __construct(private readonly GuidePreferenceRepository $preferences) {}

    public function handle(SetGuideEnabledCommand $command): void
    {
        $this->preferences->setEnabled($command->userId, $command->enabled);
    }
}
