<?php
namespace Src\Shared\Infrastructure\Persistence\Eloquent;

use Src\Shared\Domain\Feature\FeatureFlagRepository;
use Src\Shared\Domain\Feature\FeatureFlag;
use Src\Shared\Infrastructure\Persistence\Eloquent\Model\FeatureFlagModel;

class EloquentFeatureFlagRepository implements FeatureFlagRepository
{
    public function all(): array
    {
        $featureFlags = FeatureFlagModel::all();
        return $featureFlags->map(fn ($flag) => new FeatureFlag($flag->key, $flag->enabled, $flag->module))->toArray();
    }

    public function findByKey(string $key): ?FeatureFlag
    {
        $featureFlag = FeatureFlagModel::where('key', $key)->first();
        if (!$featureFlag) {
            return null;
        }
        return new FeatureFlag($featureFlag->key, $featureFlag->enabled, $featureFlag->module);
    }

    public function toggle(string $key): \Src\Shared\Domain\Feature\FeatureFlag
    {
        $featureFlag = FeatureFlagModel::where('key', $key)->firstOrFail();
        $featureFlag->enabled = !$featureFlag->enabled;
        $featureFlag->save();

        $featureFlag = new FeatureFlag($featureFlag->key, $featureFlag->enabled, $featureFlag->module);
        return $featureFlag;
    }
}