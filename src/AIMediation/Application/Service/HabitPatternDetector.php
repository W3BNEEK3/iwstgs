<?php
namespace Src\AIMediation\Application\Service;

use Src\AIMediation\Domain\Habit\HabitFlagRepository;
use Src\EvalEngine\Application\Query\GetRecentDimensionTiers\GetRecentDimensionTiersQuery;
use Src\Shared\Application\Bus\QueryBus;

/**
 * Implementation Plan §9.3. Called by PostEvaluationRouter after every
 * evaluation (not just failing ones — a habit is about a repeated pattern
 * across submissions, not any single outcome). For each dimension this
 * submission scored below proficient on, checks whether the last 3
 * evaluations of that dimension were ALSO all below proficient; if so,
 * records/reinforces a HabitFlag. Actual injection happens later, at the
 * next scenario transition (SuggestionTaskInjector), not immediately.
 */
final class HabitPatternDetector
{
    private const PATTERN_THRESHOLD = 3;
    private const PASSING_TIERS = ['proficient', 'distinguished'];

    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly HabitFlagRepository $habitFlags,
    ) {}

    /** @param \Src\EvalEngine\Domain\Evaluation\DimensionEvaluationSummary[] $dimensions */
    public function detect(string $learnerId, string $taskId, array $dimensions): void
    {
        foreach ($dimensions as $dimension) {
            if (in_array($dimension->tierAchieved, self::PASSING_TIERS, true)) {
                continue;
            }

            $recentTiers = $this->queryBus->ask(new GetRecentDimensionTiersQuery(
                $learnerId,
                $dimension->dimensionId,
                self::PATTERN_THRESHOLD,
            ));

            if (count($recentTiers) < self::PATTERN_THRESHOLD) {
                continue;
            }

            $allBelowProficient = true;
            foreach ($recentTiers as $tier) {
                if (in_array($tier, self::PASSING_TIERS, true)) {
                    $allBelowProficient = false;
                    break;
                }
            }

            if (! $allBelowProficient) {
                continue;
            }

            $this->habitFlags->recordObservation(
                $learnerId,
                "Repeated below-proficient performance in {$dimension->taskDimensionLabel} ({$dimension->dimensionId})",
                $taskId,
            );
        }
    }
}
