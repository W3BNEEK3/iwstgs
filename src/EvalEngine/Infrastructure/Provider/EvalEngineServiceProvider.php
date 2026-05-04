<?php

namespace Src\EvalEngine\Infrastructure\Provider;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * EvalEngine bounded context service provider.
 *
 * Owns: EvaluationResult, DimensionEvaluation, post-evaluation routing logic.
 * Phase 8 will add EvaluationService and PostEvaluationRouter bindings.
 */
class EvalEngineServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        /*Route::middleware('web')
            ->prefix('eval_engine')
            ->name('eval_engine.')
            ->group(base_path('routes/eval_engine.php'));*/
    }
}
