<?php

namespace Src\AIMediation\Infrastructure\Persistence\Eloquent\Repository;

use Src\AIMediation\Domain\Review\HumanReviewEntry;
use Src\AIMediation\Domain\Review\HumanReviewRepository;
use Src\AIMediation\Infrastructure\Persistence\Eloquent\Mapper\HumanReviewEntryMapper;
use Src\AIMediation\Infrastructure\Persistence\Eloquent\Model\HumanReviewQueueModel;

final class EloquentHumanReviewRepository implements HumanReviewRepository
{
    public function __construct(private readonly HumanReviewEntryMapper $mapper) {}

    public function save(HumanReviewEntry $entry): void
    {
        $attributes = $this->mapper->toAttributes($entry);
        $id = $attributes['id'];
        unset($attributes['id']);

        HumanReviewQueueModel::updateOrCreate(['id' => $id], $attributes);
    }

    public function findById(string $id): ?HumanReviewEntry
    {
        $model = HumanReviewQueueModel::find($id);
        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function findActive(): array
    {
        return HumanReviewQueueModel::whereIn('status', ['pending', 'in_review'])
            ->orderBy('queued_at')
            ->get()
            ->map(fn (HumanReviewQueueModel $m) => $this->mapper->toEntity($m))
            ->all();
    }
}
