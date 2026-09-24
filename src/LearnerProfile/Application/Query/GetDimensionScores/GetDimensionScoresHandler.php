<?php
namespace Src\LearnerProfile\Application\Query\GetDimensionScores;

use Src\LearnerProfile\Domain\Profile\DimensionScoreEntry;
use Src\LearnerProfile\Domain\Profile\DimensionScoreRepository;

final class GetDimensionScoresHandler
{
    public function __construct(private readonly DimensionScoreRepository $scores) {}

    /** @return DimensionScoreEntry[] */
    public function handle(GetDimensionScoresQuery $query): array
    {
        return $this->scores->findAllForLearner($query->learnerId);
    }
}
