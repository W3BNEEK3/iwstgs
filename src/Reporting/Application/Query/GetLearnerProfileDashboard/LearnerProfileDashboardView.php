<?php
namespace Src\Reporting\Application\Query\GetLearnerProfileDashboard;

final class LearnerProfileDashboardView
{
    /**
     * @param ProfileDimensionView[] $dimensions six canonical dimensions, sequence-ordered
     * @param \Src\LearnerProfile\Domain\Rank\RankEventSummary[] $rankEvents chronological
     * @param \Src\EvalEngine\Domain\Gap\GapFlagSummary[] $gapFlags
     * @param \Src\LearnerProfile\Domain\ConceptMastery\ConceptMasterySummary[] $conceptMastery
     * @param \Src\Submission\Domain\Submission\SubmissionTrajectoryEntry[] $submissions CAC trajectory, chronological
     * @param SessionHistoryEntry[] $sessions
     */
    public function __construct(
        public readonly string $rankTier,
        public readonly int $rankLevel,
        public readonly string $cacComplexity,
        public readonly string $cacAutonomy,
        public readonly string $cacContextFidelity,
        public readonly array $dimensions,
        public readonly array $rankEvents,
        public readonly array $gapFlags,
        public readonly array $conceptMastery,
        public readonly array $submissions,
        public readonly array $sessions,
    ) {}
}
