<?php

namespace Src\LearnerProfile\Application\Command\GenerateFinalCompetencyGraph;

use Src\LearnerProfile\Application\Query\GetDimensionScores\GetDimensionScoresQuery;
use Src\LearnerProfile\Application\Query\ListConceptMastery\ListConceptMasteryQuery;
use Src\LearnerProfile\Application\Query\ListRankEvents\ListRankEventsQuery;
use Src\LearnerProfile\Domain\Profile\LearnerProfileRepository;
use Src\Shared\Application\Bus\QueryBus;

/**
 * Gap 3 — Aggregates the learner's full competency state at session end into
 * a structured snapshot stored on the LearnerProfile. This snapshot powers
 * the final radar/web chart shown in the Reporting dashboard.
 *
 * The "graph" is not a separate entity — it is a computed snapshot written
 * to the learner_profile as a JSON blob (final_competency_snapshot). The
 * Reporting module reads it directly. Keeping it denormalised here means the
 * report always reflects the state at the time the session ended, even if
 * dimension scoring logic changes later.
 *
 * Note: final_competency_snapshot column is added in a separate migration
 * (2026_08_25_100002). The handler gracefully no-ops if the profile is not
 * found rather than throwing, because a missing profile is already an
 * abnormal state caught upstream.
 */
final class GenerateFinalCompetencyGraphHandler
{
    public function __construct(
        private readonly LearnerProfileRepository $profiles,
        private readonly QueryBus $queryBus,
    ) {}

    public function handle(GenerateFinalCompetencyGraphCommand $command): void
    {
        $profile = $this->profiles->findByLearnerId($command->learnerId);

        if ($profile === null) {
            return;
        }

        $dimensionScores = $this->queryBus->ask(new GetDimensionScoresQuery($command->learnerId));
        $conceptMastery  = $this->queryBus->ask(new ListConceptMasteryQuery($command->learnerId));
        $rankEvents      = $this->queryBus->ask(new ListRankEventsQuery($command->learnerId));

        $snapshot = [
            'session_id'       => $command->learnerSessionId,
            'final_rank_tier'  => $profile->currentRankTier()->value,
            'final_rank_level' => $profile->currentRankLevel(),
            'cac_trajectory'   => [
                'complexity'       => $profile->cacComplexity()->value,
                'autonomy'         => $profile->cacAutonomy()->value,
                'context_fidelity' => $profile->cacContextFidelity()->value,
            ],
            'dimension_scores' => array_map(
                static fn ($d) => [
                    'dimension_id'   => $d->dimensionId,
                    'tier'           => $d->tier->value,
                    'evidence_count' => $d->evidenceCount,
                ],
                $dimensionScores,
            ),
            'concept_mastery'  => array_map(
                static fn ($c) => [
                    'concept_id' => $c->conceptId,
                    'status'     => $c->status->value,
                ],
                $conceptMastery,
            ),
            'rank_event_count' => count($rankEvents),
            'generated_at'     => now()->toIso8601String(),
        ];

        $profile->recordFinalSnapshot($snapshot);
        $this->profiles->save($profile);
    }
}
