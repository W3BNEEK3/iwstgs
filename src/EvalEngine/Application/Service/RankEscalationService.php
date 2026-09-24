<?php
namespace Src\EvalEngine\Application\Service;

use Src\EvalEngine\Application\Query\GetRecentOverallTiers\GetRecentOverallTiersQuery;
use Src\EvalEngine\Domain\Evaluation\EvaluationComplete;
use Src\LearnerProfile\Application\Command\EscalateRank\EscalateRankCommand;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;

/**
 * Implementation Plan §9.4. Called by PostEvaluationRouter after a passing
 * evaluation. The failure-streak side of rank management (crossing the
 * threshold -> mismatch_flag_active + review_triggered rank event) was
 * already built in Phase 8 (LearnerProfile::recordEvaluationOutcome); this
 * is the mirror-image success path.
 *
 * ESCALATION_STREAK_THRESHOLD isn't specified in the source docs either
 * (same gap as Phase 8's failure threshold) — 3 consecutive proficient+
 * evaluations, matching FAILURE_STREAK_THRESHOLD's value for symmetry.
 */
final class RankEscalationService
{
    private const ESCALATION_STREAK_THRESHOLD = 3;
    private const PASSING_TIERS = ['proficient', 'distinguished'];

    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly CommandBus $commandBus,
    ) {}

    public function checkForEscalation(EvaluationComplete $event): void
    {
        $recentTiers = $this->queryBus->ask(new GetRecentOverallTiersQuery(
            $event->learnerId,
            self::ESCALATION_STREAK_THRESHOLD,
        ));

        if (count($recentTiers) < self::ESCALATION_STREAK_THRESHOLD) {
            return;
        }

        foreach ($recentTiers as $tier) {
            if (! in_array($tier, self::PASSING_TIERS, true)) {
                return;
            }
        }

        $this->commandBus->dispatch(new EscalateRankCommand(
            learnerId:      $event->learnerId,
            triggerReason:  self::ESCALATION_STREAK_THRESHOLD . ' consecutive proficient-or-better evaluations.',
        ));
    }
}
