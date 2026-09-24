<?php
namespace Src\SimExecution\Domain\Diagnostic;

/**
 * diagnostic_sessions has no domain aggregate — a one-time assessment record
 * with exactly two transitions (start, complete), same "byproduct repository"
 * reasoning as InjectedTaskCardRepository. RankAssignmentService/
 * DiagnosticCompletionService drive its lifecycle directly through this
 * interface rather than via a reconstituted entity.
 */
interface DiagnosticSessionRepository
{
    public function create(string $learnerId, DiagnosticPathway $pathway): string;

    public function findInProgressForLearner(string $learnerId): ?DiagnosticSessionSummary;

    public function findLatestForLearner(string $learnerId): ?DiagnosticSessionSummary;

    public function markComplete(string $id, string $assignedRankTier, int $assignedRankLevel): void;
}
