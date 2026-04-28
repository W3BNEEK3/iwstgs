<?php

namespace Src\Organizations\Infrastructure\Provider;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Organizations bounded context service provider.
 *
 * Phase 1 will add:
 *  - OrganisationRepository binding
 *  - Multi-tenancy scope registration (behind organisations.multi_tenancy flag)
 */
class OrganizationsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Route::middleware('web')
            ->prefix('organization')
            ->name('organization.')
            ->group(base_path('routes/organizations.php'));
    }
}
