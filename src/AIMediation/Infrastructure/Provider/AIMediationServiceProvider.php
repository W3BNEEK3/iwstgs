<?php

namespace Src\AIMediation\Infrastructure\Provider;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * AIMediation bounded context service provider.
 *
 * Owns: Claude API client, prompt construction, AimediationEvent audit log.
 * Phase 8 will add ClaudeApiClient and EvaluationPromptBuilder bindings.
 *
 * Critical invariant: this module is a tool, not an authority.
 * All AI decisions are anchored to system-defined rubric criteria.
 */
class AIMediationServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        /*Route::middleware('web')
            ->prefix('aimediation')
            ->name('aimediation.')
            ->group(base_path('routes/ai_mediation.php'));*/
    }
}
