<?php

namespace Src\Shared\Infrastructure\Feature;

use Illuminate\Support\Facades\Cache;
use Src\Shared\Domain\Feature\FeatureFlagRepository;
use Src\Shared\Infrastructure\Persistence\Eloquent\Model\FeatureFlagModel;

/**
 * Application service for checking and toggling feature flags.
 *
 * Why cache? Feature flags are checked on every request that uses @feature
 * directives or calls isEnabled(). Without caching, every page load queries
 * the database once per flag reference. Cache::remember() with a 60-second TTL
 * makes the first check hit the DB; subsequent checks within that window are free.
 *
 * Cache::forget() on enable/disable ensures the toggle takes effect within
 * one TTL window (60 seconds), not after a full cache flush.
 */
class FeatureFlagService
{
    public function __construct(
        private readonly FeatureFlagRepository $repository,
    ) {}

    public function isEnabled(string $key): bool
    {
        return Cache::remember(
            "feature_flag:{$key}",
            60,
            function () use ($key) {
                $flag = $this->repository->findByKey($key);
                return $flag?->isEnabled ?? false;
            }
        );
    }

    public function enable(string $key): void
    {
        FeatureFlagModel::where('flag_key', $key)
            ->update(['is_enabled' => true]);

        Cache::forget("feature_flag:{$key}");
    }

    public function disable(string $key): void
    {
        FeatureFlagModel::where('flag_key', $key)
            ->update(['is_enabled' => false]);

        Cache::forget("feature_flag:{$key}");
    }
}
