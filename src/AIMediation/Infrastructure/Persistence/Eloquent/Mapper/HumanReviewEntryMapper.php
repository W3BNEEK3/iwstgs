<?php

namespace Src\AIMediation\Infrastructure\Persistence\Eloquent\Mapper;

use Src\AIMediation\Domain\Review\HumanReviewEntry;
use Src\AIMediation\Domain\Review\HumanReviewEntryId;
use Src\AIMediation\Domain\Review\ReviewerDecision;
use Src\AIMediation\Domain\Review\ReviewStatus;
use Src\AIMediation\Infrastructure\Persistence\Eloquent\Model\HumanReviewQueueModel;

final class HumanReviewEntryMapper
{
    public function toEntity(HumanReviewQueueModel $m): HumanReviewEntry
    {
        return HumanReviewEntry::reconstitute(
            id:               HumanReviewEntryId::fromString($m->id),
            submissionId:     $m->submission_id,
            evaluationId:     $m->evaluation_id,
            learnerId:        $m->learner_id,
            status:           $m->status instanceof ReviewStatus ? $m->status : ReviewStatus::from($m->status),
            reviewerId:       $m->reviewer_id,
            reviewerDecision: $m->reviewer_decision instanceof ReviewerDecision
                ? $m->reviewer_decision
                : ($m->reviewer_decision !== null ? ReviewerDecision::from($m->reviewer_decision) : null),
            reviewerNotes:    $m->reviewer_notes,
            resolvedAt:       $m->resolved_at?->format('Y-m-d H:i:s'),
            queuedAt:         $m->queued_at?->format('Y-m-d H:i:s'),
        );
    }

    /** @return array attributes keyed for HumanReviewQueueModel::updateOrCreate() */
    public function toAttributes(HumanReviewEntry $entity): array
    {
        return $entity->toPrimitives();
    }
}
