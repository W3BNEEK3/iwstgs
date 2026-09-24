<?php

namespace Src\Shared\Infrastructure\Feature;

use Illuminate\Support\Facades\Cache;
use Src\Shared\Domain\Feature\FeatureFlag;
use Src\Shared\Domain\Feature\FeatureFlagRepository;
use Src\Shared\Infrastructure\Persistence\Eloquent\Model\FeatureFlagModel;

/**
 * Eloquent implementation of FeatureFlagRepository.
 *
 * This class is the only place that touches FeatureFlagModel.
 * It maps the Eloquent row to the domain FeatureFlag object — this mapping
 * is called an "anti-corruption layer": it prevents Eloquent's structure from
 * leaking into domain code.
 */
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
            ->map(fn($m) => new FeatureFlag($m->flag_key, $m->is_enabled, $m->module))
            ->all();
    }

    public function toggle(string $key): FeatureFlag
    {
        $model = FeatureFlagModel::where('flag_key', $key)->firstOrFail();
        $model->is_enabled = ! $model->is_enabled;
        $model->save();

        // Same cache key FeatureFlagService::isEnabled() reads — must be busted
        // here too, or a toggle via this repository silently doesn't take
        // effect for up to the 60s TTL, since FeatureMiddleware/@feature never
        // hit the database directly.
        Cache::forget("feature_flag:{$key}");

        return new FeatureFlag(
            flagKey:   $model->flag_key,
            isEnabled: $model->is_enabled,
            module:    $model->module,
        );
    }
}
