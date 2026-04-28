<?php

namespace Src\Submission\Infrastructure\Provider;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Submission bounded context service provider.
 *
 * Owns: SubmissionPackage, SubmissionArtifact.
 * Phase 7 will add SubmissionService and PlanningSnapshotAssembler bindings.
 */
class SubmissionServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Route::middleware('web')
            ->prefix('submission')
            ->name('submission.')
            ->group(base_path('routes/submission.php'));
    }
}
