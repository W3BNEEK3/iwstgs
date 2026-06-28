<?php

namespace Src\SimExecution\Infrastructure\Provider;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * SimExecution bounded context service provider.
 *
 * Owns: LearnerSession, LearnerSprint, LearnerBacklogItem, InjectedTaskCard.
 * Phase 6 will add SprintService and EnrolmentService bindings.
 */
class SimExecutionServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Route::middleware('web')->group(base_path('routes/learn.php'));
    }
}
