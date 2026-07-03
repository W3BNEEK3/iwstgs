<?php
namespace Src\Shared\Infrastructure\Feature;

use Illuminate\Support\Facades\DB;
use Src\Shared\Domain\Feature\FeatureFlag;
use Src\Shared\Domain\Feature\FeatureFlagRepository;
use Src\Shared\Infrastructure\Persistence\Eloquent\Model\FeatureFlagModel;

class EloquentFeatureFlagRepository implements FeatureFlagRepository
{
    public function findByKey(string $key): ?FeatureFlag
    {
        $model = FeatureFlagModel::where('flag_key', $key)->first();

        if ($model === null) {
            return null;
        }

        return new FeatureFlag(
            flagKey:   $model->flag_key,
            isEnabled: $model->is_enabled,
            module:    $model->module,
        );
    }

    /** @return FeatureFlag[] */
    public function all(): array
    {
        return FeatureFlagModel::all()
            ->map(fn($m) => new FeatureFlag(
                flagKey:   $m->flag_key,
                isEnabled: $m->is_enabled,
                module:    $m->module,
            ))
            ->all();
    }

    public function toggle(string $key): FeatureFlag
    {
        $model = FeatureFlagModel::where('flag_key', $key)->firstOrFail();

        DB::transaction(function () use ($model) {
            $model->is_enabled = !$model->is_enabled;
            $model->save();
        });

        return new FeatureFlag(
            flagKey:   $model->flag_key,
            isEnabled: $model->is_enabled,
            module:    $model->module,
        );
    }
}
