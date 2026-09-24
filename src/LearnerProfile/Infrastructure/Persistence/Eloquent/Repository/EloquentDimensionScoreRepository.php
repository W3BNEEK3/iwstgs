<?php

namespace Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Str;
use Src\LearnerProfile\Domain\Profile\DimensionScoreEntry;
use Src\LearnerProfile\Domain\Profile\DimensionScoreRepository;
use Src\LearnerProfile\Domain\Profile\DimensionTier;
use Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Model\DimensionScoreModel;

final class EloquentDimensionScoreRepository implements DimensionScoreRepository
{
    public function recordEvidence(string $learnerId, string $dimensionId, DimensionTier $tier): void
    {
        $model = DimensionScoreModel::where('learner_id', $learnerId)
            ->where('dimension_id', $dimensionId)
            ->first();

        if ($model === null) {
            DimensionScoreModel::create([
                'id'              => (string) Str::uuid(),
                'learner_id'      => $learnerId,
                'dimension_id'    => $dimensionId,
                'tier'            => $tier,
                'evidence_count'  => 1,
                'last_updated_at' => now(),
            ]);
            return;
        }

        $model->update([
            'tier'            => $tier,
            'evidence_count'  => $model->evidence_count + 1,
            'last_updated_at' => now(),
        ]);
    }

    public function findAllForLearner(string $learnerId): array
    {
        return DimensionScoreModel::where('learner_id', $learnerId)
            ->get()
            ->map(fn (DimensionScoreModel $m) => new DimensionScoreEntry(
                dimensionId:    $m->dimension_id,
                tier:           $m->tier,
                evidenceCount:  $m->evidence_count,
            ))
            ->all();
    }
}
