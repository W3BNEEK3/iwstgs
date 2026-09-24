<?php
namespace Src\AIMediation\Application\Command\ResolveReview;

final class ResolveReviewCommand
{
    /** @param string $decision one of proficient|not_proficient|escalate */
    public function __construct(
        public readonly string $reviewEntryId,
        public readonly string $decision,
        public readonly ?string $notes,
    ) {}
}
