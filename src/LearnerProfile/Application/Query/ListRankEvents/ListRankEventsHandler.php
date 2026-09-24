<?php
namespace Src\LearnerProfile\Application\Query\ListRankEvents;

use Src\LearnerProfile\Domain\Rank\RankEventRepository;
use Src\LearnerProfile\Domain\Rank\RankEventSummary;

final class ListRankEventsHandler
{
    public function __construct(private readonly RankEventRepository $rankEvents) {}

    /** @return RankEventSummary[] */
    public function handle(ListRankEventsQuery $query): array
    {
        return $this->rankEvents->findAllForLearner($query->learnerId);
    }
}
