<?php

namespace Src\Reporting\Infrastructure\Provider;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Reporting bounded context service provider.
 *
 * Reads from all other contexts — writes to none. Owns no source data.
 * Phase 10 will add QualificationAssessor and reporting query bindings.
 */
class ReportingServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Route::middleware('web')
            ->group(base_path('routes/reporting.php'));
    }
}
