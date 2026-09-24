<?php
namespace Src\AIMediation\Domain\Review;

use Src\Shared\Domain\AggregateRoot;
use Src\AIMediation\Domain\Exceptions\InvalidReviewTransitionException;

/**
 * A queued human review of an uncertain Claude evaluation — a real
 * lifecycle aggregate (pending -> in_review -> resolved), unlike most of
 * this system's write-once byproduct records, because a reviewer genuinely
 * loads, works on, and resolves one of these over time.
 */
final class HumanReviewEntry extends AggregateRoot
{
    public function __construct(
        private readonly HumanReviewEntryId $id,
        private readonly string $submissionId,
        private readonly string $evaluationId,
        private readonly string $learnerId,
        private ReviewStatus $status,
        private ?string $reviewerId,
        private ?ReviewerDecision $reviewerDecision,
        private ?string $reviewerNotes,
        private ?string $resolvedAt,
        private readonly ?string $queuedAt,
    ) {}

    public static function queue(
        HumanReviewEntryId $id,
        string $submissionId,
        string $evaluationId,
        string $learnerId,
    ): self {
        return new self(
            id:               $id,
            submissionId:     $submissionId,
            evaluationId:     $evaluationId,
            learnerId:        $learnerId,
            status:           ReviewStatus::Pending,
            reviewerId:       null,
            reviewerDecision: null,
            reviewerNotes:    null,
            resolvedAt:       null,
            queuedAt:         null, // DB default (useCurrent) fills this in on insert
        );
    }

    public static function reconstitute(
        HumanReviewEntryId $id,
        string $submissionId,
        string $evaluationId,
        string $learnerId,
        ReviewStatus $status,
        ?string $reviewerId,
        ?ReviewerDecision $reviewerDecision,
        ?string $reviewerNotes,
        ?string $resolvedAt,
        ?string $queuedAt,
    ): self {
        return new self(
            $id, $submissionId, $evaluationId, $learnerId,
            $status, $reviewerId, $reviewerDecision, $reviewerNotes, $resolvedAt, $queuedAt,
        );
    }

    public function startReview(string $reviewerId): void
    {
        if ($this->status !== ReviewStatus::Pending) {
            throw new InvalidReviewTransitionException('only a pending review can be started');
        }

        $this->status = ReviewStatus::InReview;
        $this->reviewerId = $reviewerId;
    }

    public function resolve(ReviewerDecision $decision, ?string $notes): void
    {
        if ($this->status !== ReviewStatus::InReview) {
            throw new InvalidReviewTransitionException('only a review already in progress can be resolved');
        }

        $this->status = ReviewStatus::Resolved;
        $this->reviewerDecision = $decision;
        $this->reviewerNotes = $notes;
        $this->resolvedAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    public function id(): string { return (string) $this->id; }
    public function submissionId(): string { return $this->submissionId; }
    public function evaluationId(): string { return $this->evaluationId; }
    public function learnerId(): string { return $this->learnerId; }
    public function status(): ReviewStatus { return $this->status; }
    public function reviewerDecision(): ?ReviewerDecision { return $this->reviewerDecision; }
    public function reviewerNotes(): ?string { return $this->reviewerNotes; }
    public function queuedAt(): ?string { return $this->queuedAt; }

    public function toPrimitives(): array
    {
        return [
            'id'                => (string) $this->id,
            'submission_id'     => $this->submissionId,
            'evaluation_id'     => $this->evaluationId,
            'learner_id'        => $this->learnerId,
            'status'            => $this->status->value,
            'reviewer_id'       => $this->reviewerId,
            'reviewer_decision' => $this->reviewerDecision?->value,
            'reviewer_notes'    => $this->reviewerNotes,
            'resolved_at'       => $this->resolvedAt,
        ];
    }
}
