<?php
namespace Src\LearnerProfile\Application\Query\GetLearnerRank;

final class GetLearnerRankQuery
{
    public function __construct(public readonly string $learnerId) {}
}
