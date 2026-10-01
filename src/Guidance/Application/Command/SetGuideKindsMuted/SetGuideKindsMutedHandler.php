<?php
namespace Src\Guidance\Application\Command\SetGuideKindsMuted;

use Src\Guidance\Domain\GuidePreferenceRepository;
use Src\Guidance\Domain\Message\GuideKind;

final class SetGuideKindsMutedHandler
{
    public function __construct(private readonly GuidePreferenceRepository $preferences) {}

    public function handle(SetGuideKindsMutedCommand $command): void
    {
        $valid = array_map(fn (GuideKind $k) => $k->value, GuideKind::cases());

        $this->preferences->setMutedKinds($command->userId, array_values(array_intersect($command->mutedKinds, $valid)));
    }
}
