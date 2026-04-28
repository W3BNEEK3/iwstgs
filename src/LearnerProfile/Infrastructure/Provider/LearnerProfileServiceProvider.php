<?php

namespace Src\LearnerProfile\Infrastructure\Provider;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * LearnerProfile bounded context service provider.
 *
 * Owns: LearnerProfile, DimensionScore, ConceptMasteryRecord, RankEvent.
 * Phase 5 will add LearnerObserver registration and profile service bindings.
 */
class LearnerProfileServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Route::middleware('web')
            ->group(base_path('routes/learner_profile.php'));
    }
}
