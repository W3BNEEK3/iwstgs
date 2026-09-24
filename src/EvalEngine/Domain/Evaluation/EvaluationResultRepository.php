<?php
namespace Src\EvalEngine\Domain\Evaluation;

/**
 * evaluation_results — mostly write-once by EvaluationService, but
 * follow_up_prompt_text and follow_up_status are mutated by
 * PostEvaluationRouter (Gap 2) and AnswerFollowUpPromptHandler.
 */
interface EvaluationResultRepository
{
    public function create(
        string $submissionId,
        string $learnerId,
        string $overallTier,
        bool $passesThreshold,
        ?string $gapType,
        bool $isUncertain,
        ?string $followUpPromptId,
    ): string;

    public function findBySubmissionId(string $submissionId): ?EvaluationResultSummary;

    /** @return string[] overall_tier values, most recent first */
    public function findRecentOverallTiers(string $learnerId, int $limit): array;

    /**
     * Every dimension's tier_achieved across every submission tied to this
     * learner session — the diagnostic pathway's raw input for computing an
     * initial rank (RankAssignmentService). evaluation_results has no
     * learner_session_id column of its own; joins through submission_packages.
     *
     * @return string[]
     */
    public function findAllDimensionTiersForSession(string $learnerSessionId): array;

    /** @return TaskPerformanceSummary[] one row per task that has ever been evaluated, admin reporting */
    public function findPerformanceByTask(): array;

    /**
     * Gap 2 — writes the follow-up prompt text and sets follow_up_status.
     * Called by PostEvaluationRouter::routeUncertain() immediately after an
     * uncertain evaluation, and again by AnswerFollowUpPromptHandler with
     * status='answered'.
     */
    public function writeFollowUpPrompt(
        string $evaluationId,
        string $followUpPromptText,
        string $status, // 'pending' | 'answered'
    ): void;
}
