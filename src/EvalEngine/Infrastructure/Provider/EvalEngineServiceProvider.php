<?php

namespace Src\EvalEngine\Infrastructure\Provider;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Src\EvalEngine\Application\Service\PostEvaluationRouter;
use Src\EvalEngine\Domain\Evaluation\EvaluationComplete;
use Src\EvalEngine\Infrastructure\Listener\EvaluateOnSubmissionReceived;
use Src\Submission\Domain\Submission\SubmissionReceived;

/**
 * EvalEngine bounded context service provider.
 *
 * Owns: EvaluationResult, DimensionEvaluation, post-evaluation routing logic.
 */
class EvalEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Src\EvalEngine\Domain\Evaluation\EvaluationResultRepository::class,
            \Src\EvalEngine\Infrastructure\Persistence\Eloquent\Repository\EloquentEvaluationResultRepository::class,
        );

        $this->app->bind(
            \Src\EvalEngine\Domain\Evaluation\DimensionEvaluationRepository::class,
            \Src\EvalEngine\Infrastructure\Persistence\Eloquent\Repository\EloquentDimensionEvaluationRepository::class,
        );

        $this->app->bind(
            \Src\EvalEngine\Domain\FollowUp\FollowUpPromptRepository::class,
            \Src\EvalEngine\Infrastructure\Persistence\Eloquent\Repository\EloquentFollowUpPromptRepository::class,
        );

        $this->app->bind(
            \Src\EvalEngine\Domain\Gap\GapFlagRepository::class,
            \Src\EvalEngine\Infrastructure\Persistence\Eloquent\Repository\EloquentGapFlagRepository::class,
        );

        $this->app->bind(
            \Src\EvalEngine\Domain\Mismatch\MismatchFlagRepository::class,
            \Src\EvalEngine\Infrastructure\Persistence\Eloquent\Repository\EloquentMismatchFlagRepository::class,
        );
    }

    public function boot(): void
    {
        Event::listen(SubmissionReceived::class, EvaluateOnSubmissionReceived::class);
        Event::listen(EvaluationComplete::class, PostEvaluationRouter::class);
    }
}
