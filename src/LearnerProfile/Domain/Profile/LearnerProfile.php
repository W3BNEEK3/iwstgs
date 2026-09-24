<?php

namespace Src\LearnerProfile\Domain\Profile;

use Src\Shared\Domain\AggregateRoot;

final class LearnerProfile extends AggregateRoot
{
    /**
     * Not specified anywhere in the Business Logic Document or Implementation
     * Plan — both only say "a defined failure threshold" without a number.
     * 3 consecutive failures is a reasonable, documented default; revisit if
     * a specific value is ever decided.
     */
    private const FAILURE_STREAK_THRESHOLD = 3;

    /**
     * Same "not specified anywhere, reasonable documented default" reasoning
     * as FAILURE_STREAK_THRESHOLD above — BLD §7.3 requires "consistent
     * performance" before escalating Autonomy/Context Fidelity but names no
     * number. Mirrors the failure threshold for symmetry.
     */
    private const SUCCESS_STREAK_THRESHOLD = 3;

    /** @param DimensionScoreEntry[] $dimensionScores */
    public function __construct(
        private readonly LearnerProfileId $id,
        private readonly string $learnerId,
        private RankTier $currentRankTier,
        private int      $currentRankLevel,
        private CacLevel $cacComplexity,
        private CacLevel $cacAutonomy,
        private CacLevel $cacContextFidelity,
        private bool     $mismatchFlagActive,
        private int      $failureStreak,
        private int      $successStreak = 0,
        private array    $dimensionScores = [],
        /** Gap 3 — set once by GenerateFinalCompetencyGraphHandler on project completion. */
        private ?array   $finalCompetencySnapshot = null,
    ) {}

    /**
     * The only way a profile is ever created. There is no controller and no user-facing
     * "create profile" action — this runs once, automatically, the moment a person
     * becomes a learner (see BootstrapLearnerProfileOnEnrolment). Bootstrap defaults come
     * straight from the Implementation Plan's Phase 5.1 column defaults: Junior-1, low
     * complexity, mid autonomy, low context fidelity.
     *
     * @param string[] $dimensionIds the six canonical dimension IDs, read from Competency
     *                                via ListCompetenceDimensionsQuery — never hard-coded
     *                                here, so a future seventh dimension needs no code change.
     */
    public static function bootstrap(LearnerProfileId $id, string $learnerId, array $dimensionIds): self
    {
        $dimensionScores = array_map(
            static fn (string $dimensionId) => new DimensionScoreEntry($dimensionId, DimensionTier::Untested, evidenceCount: 0),
            $dimensionIds,
        );

        $profile = new self(
            id:                 $id,
            learnerId:          $learnerId,
            currentRankTier:    RankTier::Junior,
            currentRankLevel:   1,
            cacComplexity:      CacLevel::Low,
            cacAutonomy:        CacLevel::Mid,
            cacContextFidelity: CacLevel::Low,
            mismatchFlagActive: false,
            failureStreak:      0,
            successStreak:      0,
            dimensionScores:    $dimensionScores,
        );

        $profile->recordEvent(new LearnerProfileBootstrapped(
            learnerProfileId: (string) $id,
            learnerId:        $learnerId,
            rankTier:         RankTier::Junior->value,
            rankLevel:        1,
        ));

        return $profile;
    }

    public static function reconstitute(
        LearnerProfileId $id,
        string $learnerId,
        RankTier $currentRankTier,
        int      $currentRankLevel,
        CacLevel $cacComplexity,
        CacLevel $cacAutonomy,
        CacLevel $cacContextFidelity,
        bool     $mismatchFlagActive,
        int      $failureStreak,
        int      $successStreak,
    ): self {
        // dimensionScores is deliberately left empty on a reconstituted profile — see the
        // note under toPrimitives() below for why.
        return new self(
            $id, $learnerId, $currentRankTier, $currentRankLevel,
            $cacComplexity, $cacAutonomy, $cacContextFidelity,
            $mismatchFlagActive, $failureStreak, $successStreak,
        );
    }

    /**
     * A passing evaluation resets the streak; a failing one increments it and,
     * once it crosses the threshold, flags the profile for review (Business
     * Logic §2: "if a learner fails beyond a defined failure threshold... the
     * system initiates a rank review process").
     */
    public function recordEvaluationOutcome(bool $passed): void
    {
        if ($passed) {
            $this->failureStreak = 0;
            return;
        }

        $this->failureStreak++;

        if ($this->failureStreak >= self::FAILURE_STREAK_THRESHOLD && ! $this->mismatchFlagActive) {
            $this->mismatchFlagActive = true;
            $this->recordEvent(new RankReviewTriggered(
                learnerProfileId: (string) $this->id,
                learnerId:        $this->learnerId,
                failureStreak:    $this->failureStreak,
            ));
        }
    }

    /**
     * BLD §7.3's adjustment priority order: Autonomy escalates before Context
     * Fidelity, both ahead of Complexity (which is scenario-transition-scoped,
     * see escalateComplexityForScenarioTransition()). Independent of
     * failureStreak/recordEvaluationOutcome() on purpose — callers only ever
     * invoke this for non-diagnostic evaluations (diagnostic tasks stay fixed
     * at mid/mid/mid per §4.2), so this never needs its own diagnostic guard.
     */
    public function adjustCacFromSubmission(bool $passed): void
    {
        if (! $passed) {
            $this->successStreak = 0;
            return;
        }

        $this->successStreak++;

        if ($this->successStreak < self::SUCCESS_STREAK_THRESHOLD) {
            return;
        }

        if ($this->cacAutonomy !== CacLevel::High) {
            $from = $this->cacAutonomy;
            $this->cacAutonomy = $this->cacAutonomy->next();
            $this->successStreak = 0;
            $this->recordEvent(new CacDimensionAdjusted(
                learnerProfileId: (string) $this->id,
                learnerId:        $this->learnerId,
                dimension:        'autonomy',
                fromLevel:        $from->value,
                toLevel:          $this->cacAutonomy->value,
                reason:           'success_streak',
            ));
            return;
        }

        if ($this->cacContextFidelity !== CacLevel::High) {
            $from = $this->cacContextFidelity;
            $this->cacContextFidelity = $this->cacContextFidelity->next();
            $this->successStreak = 0;
            $this->recordEvent(new CacDimensionAdjusted(
                learnerProfileId: (string) $this->id,
                learnerId:        $this->learnerId,
                dimension:        'context_fidelity',
                fromLevel:        $from->value,
                toLevel:          $this->cacContextFidelity->value,
                reason:           'success_streak',
            ));
            return;
        }

        // Both maxed — Complexity only escalates at scenario transitions, so
        // there's nothing to step mid-scenario. Cap the streak rather than
        // let it grow unbounded.
        $this->successStreak = self::SUCCESS_STREAK_THRESHOLD;
    }

    /**
     * BLD §7.3's note that "Complexity escalation typically occurs at
     * scenario transitions, not within a single scenario" — called by
     * ScenarioTransitionService once it's confirmed consistently strong
     * performance across the just-completed scenario. §7.4's scaffolding
     * rule: stepping into a new Complexity level steps Autonomy back down
     * one notch (more guidance for the unfamiliar territory), even if
     * Autonomy had climbed during the previous scenario. Context Fidelity is
     * left untouched — the BLD names Autonomy specifically, not both.
     */
    public function escalateComplexityForScenarioTransition(): bool
    {
        if ($this->cacComplexity === CacLevel::High) {
            return false;
        }

        $fromComplexity = $this->cacComplexity;
        $this->cacComplexity = $this->cacComplexity->next();
        $this->recordEvent(new CacDimensionAdjusted(
            learnerProfileId: (string) $this->id,
            learnerId:        $this->learnerId,
            dimension:        'complexity',
            fromLevel:        $fromComplexity->value,
            toLevel:          $this->cacComplexity->value,
            reason:           'scenario_transition',
        ));

        if ($this->cacAutonomy !== CacLevel::Low) {
            $fromAutonomy = $this->cacAutonomy;
            $this->cacAutonomy = $this->cacAutonomy->previous();
            $this->recordEvent(new CacDimensionAdjusted(
                learnerProfileId: (string) $this->id,
                learnerId:        $this->learnerId,
                dimension:        'autonomy',
                fromLevel:        $fromAutonomy->value,
                toLevel:          $this->cacAutonomy->value,
                reason:           'scaffolding_on_escalation',
            ));
        }

        return true;
    }

    /**
     * Implementation Plan §9.4: sub-level progression within a tier (Junior-1
     * -> Junior-2) vs a tier change (Junior-3 -> Mid-1) — the caller decides
     * which happened by comparing the tier before/after this call, since that
     * distinction only matters for which RankEventType gets logged, not for
     * anything this aggregate needs to know internally.
     *
     * Returns false (no-op) at Senior-3, the ceiling — there's nowhere higher
     * to escalate to.
     */
    public function escalate(): bool
    {
        [$nextTier, $nextLevel] = self::nextRankPosition($this->currentRankTier, $this->currentRankLevel);
        if ($nextTier === null) {
            return false;
        }

        $this->currentRankTier = $nextTier;
        $this->currentRankLevel = $nextLevel;

        return true;
    }

    /**
     * The diagnostic pathway's one-time write of a computed starting rank —
     * unlike escalate()'s single-step move, this sets an arbitrary target
     * position directly. Only ever called once per learner, by
     * AssignInitialRankHandler, right after the diagnostic scenario
     * completes; nothing enforces that here — the guard lives in
     * DiagnosticSession's own status (can't complete twice).
     */
    public function assignInitialRank(RankTier $tier, int $level): void
    {
        $this->currentRankTier = $tier;
        $this->currentRankLevel = $level;
    }

    /** @return array{0: ?RankTier, 1: ?int} */
    private static function nextRankPosition(RankTier $tier, int $level): array
    {
        if ($level < 3) {
            return [$tier, $level + 1];
        }

        return match ($tier) {
            RankTier::Junior => [RankTier::Mid, 1],
            RankTier::Mid    => [RankTier::Senior, 1],
            RankTier::Senior => [null, null],
        };
    }

    public function id(): string { return (string) $this->id; }
    public function learnerProfileId(): LearnerProfileId { return $this->id; }
    public function learnerId(): string { return $this->learnerId; }
    public function currentRankTier(): RankTier { return $this->currentRankTier; }
    public function currentRankLevel(): int { return $this->currentRankLevel; }
    public function cacComplexity(): CacLevel { return $this->cacComplexity; }
    public function cacAutonomy(): CacLevel { return $this->cacAutonomy; }
    public function cacContextFidelity(): CacLevel { return $this->cacContextFidelity; }
    public function mismatchFlagActive(): bool { return $this->mismatchFlagActive; }
    public function failureStreak(): int { return $this->failureStreak; }
    public function successStreak(): int { return $this->successStreak; }
    public function finalCompetencySnapshot(): ?array { return $this->finalCompetencySnapshot; }

    /** @return DimensionScoreEntry[] */
    public function dimensionScores(): array { return $this->dimensionScores; }

    /**
     * Gap 3 — called once by GenerateFinalCompetencyGraphHandler when all
     * project scenarios are complete. The snapshot is write-once; a second
     * call on an already-completed profile is a no-op to prevent accidental
     * overwrites.
     */
    public function recordFinalSnapshot(array $snapshot): void
    {
        if ($this->finalCompetencySnapshot !== null) {
            return; // already recorded — no-op
        }

        $this->finalCompetencySnapshot = $snapshot;
    }

    /**
     * Flat, DB-shaped representation for the repository. dimensionScores is NOT
     * reloaded on reconstitute() — updating a single score later is Phase 8/9's job,
     * working against DimensionScoreModel directly, not round-tripping this whole
     * aggregate. This array only ever has entries the moment bootstrap() just built them.
     */
    public function toPrimitives(): array
    {
        return [
            'id'                          => (string) $this->id,
            'learner_id'                  => $this->learnerId,
            'current_rank_tier'           => $this->currentRankTier->value,
            'current_rank_level'          => $this->currentRankLevel,
            'cac_complexity'              => $this->cacComplexity->value,
            'cac_autonomy'               => $this->cacAutonomy->value,
            'cac_context_fidelity'        => $this->cacContextFidelity->value,
            'mismatch_flag_active'        => $this->mismatchFlagActive,
            'failure_streak'              => $this->failureStreak,
            'success_streak'              => $this->successStreak,
            'final_competency_snapshot'   => $this->finalCompetencySnapshot,
            'dimension_scores'            => array_map(
                static fn (DimensionScoreEntry $e) => [
                    'dimension_id'   => $e->dimensionId,
                    'tier'           => $e->tier->value,
                    'evidence_count' => $e->evidenceCount,
                ],
                $this->dimensionScores,
            ),
        ];
    }
}
