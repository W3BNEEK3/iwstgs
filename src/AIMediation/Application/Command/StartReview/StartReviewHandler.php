<?php
namespace Src\AIMediation\Application\Command\StartReview;

use Src\AIMediation\Domain\Exceptions\ReviewEntryNotFoundException;
use Src\AIMediation\Domain\Review\HumanReviewRepository;

final class StartReviewHandler
{
    public function __construct(private readonly HumanReviewRepository $humanReviews) {}

    public function handle(StartReviewCommand $command): void
    {
        $entry = $this->humanReviews->findById($command->reviewEntryId);
        if ($entry === null) {
            throw new ReviewEntryNotFoundException();
        }

        $entry->startReview($command->reviewerId);
        $this->humanReviews->save($entry);
    }
}
