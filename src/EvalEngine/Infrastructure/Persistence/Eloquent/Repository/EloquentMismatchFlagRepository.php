<?php

namespace Src\EvalEngine\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Str;
use Src\EvalEngine\Domain\Mismatch\MismatchFlagRepository;
use Src\EvalEngine\Infrastructure\Persistence\Eloquent\Model\MismatchFlagModel;

final class EloquentMismatchFlagRepository implements MismatchFlagRepository
{
    public function create(string $learnerId, string $mismatchType): string
    {
        $model = MismatchFlagModel::create([
            'id'            => (string) Str::uuid(),
            'learner_id'    => $learnerId,
            'mismatch_type' => $mismatchType,
        ]);

        return $model->id;
    }
}
