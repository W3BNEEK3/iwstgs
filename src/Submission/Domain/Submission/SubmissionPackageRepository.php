<?php
namespace Src\Submission\Domain\Submission;

/**
 * submission_packages has no domain aggregate — write-once, never updated by
 * the learner (same reasoning as RoleEnrolmentRepository/RankEventRepository).
 * Phase 8 (EvalEngine) reads these rows to grade; it writes results to its
 * own tables rather than mutating this one.
 */
interface SubmissionPackageRepository
{
    public function create(
        string $learnerSessionId,
        string $learnerId,
        string $taskId,
        string $scenarioId,
        ?string $sprintId,
        int $attemptNumber,
        ?string $layer1Text,
        ?array $layer2ArtifactIds,
        ?string $layer3Code,
        ?array $layer3ExecutionResult,
        ?array $layer4PlanningSnapshot,
        string $cacComplexityAtSub,
        string $cacAutonomyAtSub,
        string $cacContextAtSub,
        string $rankAtSubmission,
    ): string;

    /** How many attempts already exist for this task within this session — the next one is +1. */
    public function countAttempts(string $learnerSessionId, string $taskId): int;

    public function findLatestId(string $learnerSessionId, string $taskId): ?string;

    public function findById(string $id): ?SubmissionPackageSummary;

    public function findDetailById(string $id): ?SubmissionPackageDetail;

    /** v2: marks a submission as made from a commit in the learner's repository; CI starts as pending. */
    public function attachCommit(string $id, string $commitSha, string $baseSha, array $diffSummary): void;

    /** @param array|null $report AcceptanceResult::toArray() */
    public function recordCi(string $id, string $status, ?string $runUrl, ?array $report): void;

    /** @return string[] ids of every attempt at this task in this session, newest first */
    public function attemptIds(string $learnerSessionId, string $taskId): array;

    /** @return string[] submissions still waiting for CI, oldest first */
    public function pendingCiIds(?string $learnerSessionId = null, ?string $commitSha = null): array;

    /** @return SubmissionTrajectoryEntry[] every submission this learner has ever made, oldest first */
    public function findAllForLearner(string $learnerId): array;
}
