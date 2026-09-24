<?php

namespace Src\Reporting\Infrastructure\Provider;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Reporting bounded context service provider.
 *
 * Reads from all other contexts — writes to none. Owns no source data.
 * Route names/prefixes/role-gating are declared per-group inside
 * routes/reporting.php itself (learner-facing + admin groups side by side),
 * same convention as routes/learn.php — this provider just mounts it under
 * the 'web' middleware group.
 */
class ReportingServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Route::middleware('web')->group(base_path('routes/reporting.php'));
    }
}
