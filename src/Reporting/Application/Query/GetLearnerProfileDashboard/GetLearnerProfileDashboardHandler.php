<?php
namespace Src\Reporting\Application\Query\GetLearnerProfileDashboard;

use Src\Competency\Application\Query\ListCompetenceDimensions\ListCompetenceDimensionsQuery;
use Src\Competency\Domain\Dimension\CompetenceDimensionSummary;
use Src\EvalEngine\Application\Query\ListGapFlags\ListGapFlagsQuery;
use Src\LearnerProfile\Application\Query\GetLearnerRank\GetLearnerRankQuery;
use Src\LearnerProfile\Application\Query\GetLearnerRank\LearnerRankView;
use Src\LearnerProfile\Application\Query\GetDimensionScores\GetDimensionScoresQuery;
use Src\LearnerProfile\Application\Query\ListConceptMastery\ListConceptMasteryQuery;
use Src\LearnerProfile\Application\Query\ListRankEvents\ListRankEventsQuery;
use Src\LearnerProfile\Domain\Profile\DimensionScoreEntry;
use Src\LearnerProfile\Domain\Profile\DimensionTier;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Query\GetLearnerIdForUser\GetLearnerIdForUserQuery;
use Src\SimExecution\Application\Query\GetLearnerSession\LearnerSessionView;
use Src\SimExecution\Application\Query\ListSessionsForLearner\ListSessionsForLearnerQuery;
use Src\Simulation\Application\Query\GetProject\GetProjectQuery;
use Src\Submission\Application\Query\ListSubmissionsForLearner\ListSubmissionsForLearnerQuery;

/**
 * Reporting composes — it owns no repository bindings to other modules'
 * tables, only QueryBus reads, matching the module's own stated boundary
 * ("Reads from all other contexts — writes to none. Owns no source data.")
 * and the established cross-module read pattern (GetSessionOnboardingHandler).
 */
final class GetLearnerProfileDashboardHandler
{
    public function __construct(private readonly QueryBus $queryBus) {}

    public function handle(GetLearnerProfileDashboardQuery $query): ?LearnerProfileDashboardView
    {
        if ($query->userId === null) {
            return null;
        }

        $learnerId = $this->queryBus->ask(new GetLearnerIdForUserQuery($query->userId));
        if ($learnerId === null) {
            return null;
        }

        /** @var LearnerRankView|null $rank */
        $rank = $this->queryBus->ask(new GetLearnerRankQuery($learnerId));
        if ($rank === null) {
            return null;
        }

        /** @var CompetenceDimensionSummary[] $canonicalDimensions */
        $canonicalDimensions = $this->queryBus->ask(new ListCompetenceDimensionsQuery());

        /** @var DimensionScoreEntry[] $scores */
        $scores = $this->queryBus->ask(new GetDimensionScoresQuery($learnerId));
        $scoresByDimension = [];
        foreach ($scores as $score) {
            $scoresByDimension[$score->dimensionId] = $score;
        }

        $dimensions = array_map(
            fn (CompetenceDimensionSummary $d) => new ProfileDimensionView(
                dimensionId:    $d->id,
                shortLabel:     $d->shortLabel,
                tier:           $scoresByDimension[$d->id]->tier->value ?? DimensionTier::Untested->value,
                evidenceCount:  $scoresByDimension[$d->id]->evidenceCount ?? 0,
            ),
            $canonicalDimensions,
        );

        /** @var LearnerSessionView[] $sessions */
        $sessions = $this->queryBus->ask(new ListSessionsForLearnerQuery($learnerId));
        $sessionHistory = array_map(function (LearnerSessionView $s) {
            $project = $this->queryBus->ask(new GetProjectQuery($s->projectId));

            return new SessionHistoryEntry(
                sessionId:     $s->id,
                projectId:     $s->projectId,
                projectTitle:  $project?->title() ?? '(project unavailable)',
                status:        $s->status,
            );
        }, $sessions);

        return new LearnerProfileDashboardView(
            rankTier:            $rank->rankTier,
            rankLevel:           $rank->rankLevel,
            cacComplexity:       $rank->cacComplexity,
            cacAutonomy:         $rank->cacAutonomy,
            cacContextFidelity:  $rank->cacContextFidelity,
            dimensions:          $dimensions,
            rankEvents:          $this->queryBus->ask(new ListRankEventsQuery($learnerId)),
            gapFlags:            $this->queryBus->ask(new ListGapFlagsQuery($learnerId)),
            conceptMastery:      $this->queryBus->ask(new ListConceptMasteryQuery($learnerId)),
            submissions:         $this->queryBus->ask(new ListSubmissionsForLearnerQuery($learnerId)),
            sessions:            $sessionHistory,
        );
    }
}
