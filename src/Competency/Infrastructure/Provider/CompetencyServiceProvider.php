<?php

namespace Src\Competency\Infrastructure\Provider;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Competency bounded context service provider.
 *
 * Owns: CompetenceDimension, RoleDefinition — static canonical data.
 * Phase 3 will add model bindings. Data is seeded, not mutated at runtime.
 */
class CompetencyServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Route::middleware('web')
            ->prefix('competency')
            ->name('competency.')
            ->group(base_path('routes/competency.php'));
    }
}
