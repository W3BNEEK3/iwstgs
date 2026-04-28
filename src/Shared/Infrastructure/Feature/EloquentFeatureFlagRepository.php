<?php

namespace Src\Shared\Infrastructure\Feature;

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
}
