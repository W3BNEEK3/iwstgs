<?php
namespace Src\AIMediation\Application\Query\ListPendingReviews;

final class PendingReviewView
{
    public function __construct(
        public readonly string $id,
        public readonly string $submissionId,
        public readonly string $learnerName,
        public readonly string $taskTitle,
        public readonly ?string $queuedAt,
        public readonly string $status,
    ) {}
}
