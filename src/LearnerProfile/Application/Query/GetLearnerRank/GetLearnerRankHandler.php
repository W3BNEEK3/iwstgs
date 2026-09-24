<?php
namespace Src\LearnerProfile\Application\Query\GetLearnerRank;

use Src\LearnerProfile\Domain\Profile\LearnerProfileRepository;

final class GetLearnerRankHandler
{
    public function __construct(private readonly LearnerProfileRepository $profiles) {}

    public function handle(GetLearnerRankQuery $query): ?LearnerRankView
    {
        $profile = $this->profiles->findByLearnerId($query->learnerId);
        if ($profile === null) {
            return null;
        }

        return new LearnerRankView(
            rankTier:            $profile->currentRankTier()->value,
            rankLevel:           $profile->currentRankLevel(),
            cacComplexity:       $profile->cacComplexity()->value,
            cacAutonomy:         $profile->cacAutonomy()->value,
            cacContextFidelity:  $profile->cacContextFidelity()->value,
            failureStreak:       $profile->failureStreak(),
            mismatchFlagActive:  $profile->mismatchFlagActive(),
        );
    }
}
