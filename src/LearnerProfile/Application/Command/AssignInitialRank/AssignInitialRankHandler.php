<?php
namespace Src\LearnerProfile\Application\Command\AssignInitialRank;

use Src\LearnerProfile\Domain\Profile\LearnerProfileRepository;
use Src\LearnerProfile\Domain\Profile\RankTier;
use Src\LearnerProfile\Domain\Rank\RankEventRepository;

final class AssignInitialRankHandler
{
    public function __construct(
        private readonly LearnerProfileRepository $profiles,
        private readonly RankEventRepository $rankEvents,
    ) {}

    public function handle(AssignInitialRankCommand $command): void
    {
        $profile = $this->profiles->findByLearnerId($command->learnerId);
        if ($profile === null) {
            return;
        }

        $profile->assignInitialRank(RankTier::from($command->rankTier), $command->rankLevel);
        $this->profiles->save($profile);

        $this->rankEvents->updateInitialAssignment(
            learnerId:        $command->learnerId,
            toRankTier:       $command->rankTier,
            toRankLevel:      $command->rankLevel,
            triggerReason:    $command->triggerReason,
            sourceSessionId:  $command->sourceSessionId,
        );
    }
}
