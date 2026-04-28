<?php

namespace Src\Shared\Infrastructure\Provider;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\Shared\Application\Bus\SynchronousCommandBus;
use Src\Shared\Application\Bus\SynchronousQueryBus;
use Src\Shared\Domain\Feature\FeatureFlagRepository;
use Src\Shared\Infrastructure\Feature\EloquentFeatureFlagRepository;
use Src\Shared\Infrastructure\Feature\FeatureFlagService;

/**
 * Bootstraps the Shared kernel — the structural foundation every module builds on.
 *
 * Responsibilities:
 *  - Bind CommandBus and QueryBus interfaces to their synchronous implementations
 *  - Bind FeatureFlagRepository to its Eloquent implementation
 *  - Register the @feature / @endfeature Blade directive
 *
 * What this provider does NOT do: load routes. Route loading is each module's
 * own responsibility and belongs in that module's provider.
 */
class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FeatureFlagRepository::class, EloquentFeatureFlagRepository::class);
        $this->app->bind(CommandBus::class, SynchronousCommandBus::class);
        $this->app->bind(QueryBus::class, SynchronousQueryBus::class);
    }

    public function boot(): void
    {
        // @feature('flag.key') ... @endfeature
        // Renders enclosed content only when the named flag is enabled.
        Blade::if('feature', function (string $key) {
            return app(FeatureFlagService::class)->isEnabled($key);
        });
    }
}
