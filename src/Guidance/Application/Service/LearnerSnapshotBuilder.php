<?php
namespace Src\Guidance\Application\Service;

use Src\Competency\Application\Query\ListCompetenceDimensions\ListCompetenceDimensionsQuery;
use Src\EvalEngine\Application\Query\GetRecentOverallTiers\GetRecentOverallTiersQuery;
use Src\LearnerProfile\Application\Query\GetDimensionScores\GetDimensionScoresQuery;
use Src\LearnerProfile\Application\Query\GetLearnerRank\GetLearnerRankQuery;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Query\GetLearnerIdForUser\GetLearnerIdForUserQuery;
use Src\SimExecution\Application\Query\GetLearnerName\GetLearnerNameQuery;

/**
 * The compact, factual picture of a learner the writer is given (design doc
 * v2-05 §2). First name only; no submissions, no other learners' data.
 */
final class LearnerSnapshotBuilder
{
    public function __construct(private readonly QueryBus $queryBus) {}

    /** @return array<string, mixed> */
    public function build(string $userId): array
    {
        $learnerId = $this->queryBus->ask(new GetLearnerIdForUserQuery($userId));
        if ($learnerId === null) {
            return ['status' => 'has not started yet'];
        }

        $name = (string) $this->queryBus->ask(new GetLearnerNameQuery($learnerId));
        $rank = $this->queryBus->ask(new GetLearnerRankQuery($learnerId));

        $labels = [];
        foreach ($this->queryBus->ask(new ListCompetenceDimensionsQuery()) as $dimension) {
            $labels[$dimension->id] = $dimension->shortLabel;
        }
        $skills = [];
        foreach ($this->queryBus->ask(new GetDimensionScoresQuery($learnerId)) as $score) {
            $skills[$labels[$score->dimensionId] ?? $score->dimensionId] = $score->tier->value;
        }

        return array_filter([
            'first_name'          => trim(explode(' ', trim($name))[0]) ?: null,
            'rank'                => $rank !== null ? "{$rank->rankTier}-{$rank->rankLevel}" : null,
            'skill_levels'        => $skills ?: null,
            'recent_results'      => $this->queryBus->ask(new GetRecentOverallTiersQuery($learnerId, 5)) ?: null,
            'failures_in_a_row'   => $rank?->failureStreak,
        ], fn ($v) => $v !== null);
    }
}
