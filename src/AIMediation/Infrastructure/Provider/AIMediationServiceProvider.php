<?php

namespace Src\AIMediation\Infrastructure\Provider;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\AIMediation\Domain\Provider\AiEvaluatorClient;
use Src\AIMediation\Domain\Provider\AiTextGeneratorClient;
use Src\AIMediation\Infrastructure\Services\ClaudeApiClient;
use Src\AIMediation\Infrastructure\Services\GeminiApiClient;

/**
 * AIMediation bounded context service provider.
 *
 * Owns: AI provider clients, prompt construction, AimediationEvent audit
 * log, HumanReviewQueue.
 *
 * Critical invariant: this module is a tool, not an authority.
 * All AI decisions are anchored to system-defined rubric criteria.
 */
class AIMediationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Src\AIMediation\Domain\Audit\AimediationEventRepository::class,
            \Src\AIMediation\Infrastructure\Persistence\Eloquent\Repository\EloquentAimediationEventRepository::class,
        );

        $this->app->bind(
            \Src\AIMediation\Domain\Review\HumanReviewRepository::class,
            \Src\AIMediation\Infrastructure\Persistence\Eloquent\Repository\EloquentHumanReviewRepository::class,
        );

        $this->app->bind(
            \Src\AIMediation\Domain\Habit\HabitFlagRepository::class,
            \Src\AIMediation\Infrastructure\Persistence\Eloquent\Repository\EloquentHabitFlagRepository::class,
        );

        $this->app->bind(ClaudeApiClient::class, function () {
            return new ClaudeApiClient(
                config('services.anthropic.key'),
                config('services.anthropic.model'),
            );
        });

        $this->app->bind(GeminiApiClient::class, function () {
            return new GeminiApiClient(
                config('services.gemini.key'),
                config('services.gemini.model'),
            );
        });

        // services.ai_evaluation.provider (env AI_EVALUATION_PROVIDER) picks which
        // concrete client EvaluationService actually calls — see config/services.php.
        $this->app->bind(AiEvaluatorClient::class, function ($app) {
            return match (config('services.ai_evaluation.provider')) {
                'gemini' => $app->make(GeminiApiClient::class),
                default  => $app->make(ClaudeApiClient::class),
            };
        });

        // Same provider switch as AiEvaluatorClient — Tiroco's narrative generation
        // uses whichever provider is already configured for evaluation.
        $this->app->bind(AiTextGeneratorClient::class, function ($app) {
            return match (config('services.ai_evaluation.provider')) {
                'gemini' => $app->make(GeminiApiClient::class),
                default  => $app->make(ClaudeApiClient::class),
            };
        });
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth', 'role:content_author'])
            ->prefix('admin')
            ->name('admin.')
            ->group(base_path('routes/ai_mediation.php'));
    }
}
