<?php
namespace Src\Guidance\Application\Command\UpdateGuideSettings;

use Src\Guidance\Application\Service\GuideSettings;

final class UpdateGuideSettingsHandler
{
    public function __construct(private readonly GuideSettings $settings) {}

    public function handle(UpdateGuideSettingsCommand $command): void
    {
        $this->settings->save($command->dailyCap, $command->triggers);
    }
}
