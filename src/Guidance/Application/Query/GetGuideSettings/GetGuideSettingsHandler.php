<?php
namespace Src\Guidance\Application\Query\GetGuideSettings;

use Src\Guidance\Domain\GuidePreference;
use Src\Guidance\Domain\GuidePreferenceRepository;

final class GetGuideSettingsHandler
{
    public function __construct(private readonly GuidePreferenceRepository $preferences) {}

    public function handle(GetGuideSettingsQuery $query): GuidePreference
    {
        return $this->preferences->forUser($query->userId);
    }
}
