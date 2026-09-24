<?php
namespace Src\LearnerProfile\Application\Command\EscalateRank;

use Src\LearnerProfile\Domain\Profile\LearnerProfileRepository;
use Src\LearnerProfile\Domain\Rank\RankEventRepository;
use Src\LearnerProfile\Domain\Rank\RankEventType;

final class EscalateRankHandler
{
    public function __construct(
        private readonly LearnerProfileRepository $profiles,
        private readonly RankEventRepository $rankEvents,
    ) {}

    public function handle(EscalateRankCommand $command): void
    {
        $profile = $this->profiles->findByLearnerId($command->learnerId);
        if ($profile === null) {
            return;
        }

        $fromTier = $profile->currentRankTier();
        $fromLevel = $profile->currentRankLevel();

        if (! $profile->escalate()) {
            return; // already at the Senior-3 ceiling
        }

        $this->profiles->save($profile);

        $this->rankEvents->record(
            learnerId:        $command->learnerId,
            eventType:        $fromTier === $profile->currentRankTier() ? RankEventType::SubLevelProgression : RankEventType::Escalation,
            fromRankTier:     $fromTier->value,
            fromRankLevel:    $fromLevel,
            toRankTier:       $profile->currentRankTier()->value,
            toRankLevel:      $profile->currentRankLevel(),
            triggerReason:    $command->triggerReason,
            sourceSessionId:  null,
        );
    }
}
