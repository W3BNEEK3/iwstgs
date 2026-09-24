<?php
namespace Src\LearnerProfile\Application\Command\RecordEvaluationOutcome;

use Src\LearnerProfile\Domain\Profile\LearnerProfileRepository;
use Src\LearnerProfile\Domain\Rank\RankEventRepository;
use Src\LearnerProfile\Domain\Rank\RankEventType;

final class RecordEvaluationOutcomeHandler
{
    public function __construct(
        private readonly LearnerProfileRepository $profiles,
        private readonly RankEventRepository $rankEvents,
    ) {}

    public function handle(RecordEvaluationOutcomeCommand $command): void
    {
        $profile = $this->profiles->findByLearnerId($command->learnerId);
        if ($profile === null) {
            return;
        }

        $wasFlagged = $profile->mismatchFlagActive();
        $profile->recordEvaluationOutcome($command->passed);
        $this->profiles->save($profile);

        foreach ($profile->releaseEvents() as $event) {
            event($event);
        }

        if (! $wasFlagged && $profile->mismatchFlagActive()) {
            $this->rankEvents->record(
                learnerId:        $command->learnerId,
                eventType:        RankEventType::ReviewTriggered,
                fromRankTier:     $profile->currentRankTier()->value,
                fromRankLevel:    $profile->currentRankLevel(),
                // A review doesn't move the rank; to = from (to_rank_* is NOT NULL).
                toRankTier:       $profile->currentRankTier()->value,
                toRankLevel:      $profile->currentRankLevel(),
                triggerReason:    "Failure streak reached {$profile->failureStreak()} consecutive failures.",
                sourceSessionId:  null,
            );
        }
    }
}
