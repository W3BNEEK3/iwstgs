<?php

namespace Src\Simulation\Infrastructure\Provider;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Simulation bounded context service provider.
 *
 * Owns: ProjectTemplate, ScenarioTemplate, Task blueprints, RubricSets.
 * Phase 2–4 will add repository bindings.
 */
class SimulationServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Route::middleware('web')
            ->group(base_path('routes/simulation.php'));
    }
}
