<?php
namespace Src\LearnerProfile\Application\Query\GetDimensionScores;

final class GetDimensionScoresQuery
{
    public function __construct(public readonly string $learnerId) {}
}
