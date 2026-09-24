<?php
namespace Src\AIMediation\Domain\Review;

interface HumanReviewRepository
{
    public function save(HumanReviewEntry $entry): void;

    public function findById(string $id): ?HumanReviewEntry;

    /** @return HumanReviewEntry[] not yet resolved — pending or in_review */
    public function findActive(): array;
}
