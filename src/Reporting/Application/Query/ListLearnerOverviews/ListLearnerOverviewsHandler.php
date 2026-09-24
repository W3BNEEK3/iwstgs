<?php
namespace Src\Reporting\Application\Query\ListLearnerOverviews;

use Src\LearnerProfile\Application\Query\GetLearnerRank\GetLearnerRankQuery;
use Src\LearnerProfile\Application\Query\GetLearnerRank\LearnerRankView;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Query\ListLearners\ListLearnersQuery;
use Src\SimExecution\Application\Query\ListLearners\LearnerSummary;
use Src\SimExecution\Application\Query\ListSessionsForLearner\ListSessionsForLearnerQuery;

/**
 * Admin-only, small learner counts expected at this stage — one rank query +
 * one session-count query per learner rather than a bespoke bulk-aggregate
 * query. Revisit if the learner count ever makes this a real N+1 concern.
 */
final class ListLearnerOverviewsHandler
{
    public function __construct(private readonly QueryBus $queryBus) {}

    /** @return LearnerOverviewView[] */
    public function handle(ListLearnerOverviewsQuery $query): array
    {
        /** @var LearnerSummary[] $learners */
        $learners = $this->queryBus->ask(new ListLearnersQuery());

        $overviews = [];
        foreach ($learners as $learner) {
            /** @var LearnerRankView|null $rank */
            $rank = $this->queryBus->ask(new GetLearnerRankQuery($learner->id));
            if ($rank === null) {
                continue; // profile not yet bootstrapped — shouldn't happen, skip defensively
            }

            $sessionCount = count($this->queryBus->ask(new ListSessionsForLearnerQuery($learner->id)));

            $overviews[] = new LearnerOverviewView(
                learnerId:            $learner->id,
                fullname:             $learner->fullname,
                entryCategory:        $learner->entryCategory,
                rankTier:             $rank->rankTier,
                rankLevel:            $rank->rankLevel,
                failureStreak:        $rank->failureStreak,
                mismatchFlagActive:   $rank->mismatchFlagActive,
                sessionCount:         $sessionCount,
            );
        }

        return $overviews;
    }
}
