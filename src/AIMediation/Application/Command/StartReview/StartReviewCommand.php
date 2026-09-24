<?php
namespace Src\AIMediation\Application\Command\StartReview;

final class StartReviewCommand
{
    public function __construct(
        public readonly string $reviewEntryId,
        public readonly string $reviewerId,
    ) {}
}
