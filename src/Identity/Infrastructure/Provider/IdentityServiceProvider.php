<?php

namespace Src\Identity\Infrastructure\Provider;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Identity bounded context service provider.
 *
 * Phase 1 will add:
 *  - Binding of LearnerRepository interface to EloquentLearnerRepository
 *  - Registration of auth middleware and guards
 */
class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Route::middleware('web')
            ->group(base_path('routes/identity.php'));
    }
}
