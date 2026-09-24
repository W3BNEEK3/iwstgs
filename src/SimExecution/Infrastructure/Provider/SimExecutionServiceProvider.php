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
    public function register(): void
    {
        $this->app->bind(
            \Src\SimExecution\Domain\Enrollment\LearnerRepository::class,
            \Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository\EloquentLearnerRepository::class,
        );

        $this->app->bind(
            \Src\SimExecution\Domain\Session\LearnerSessionRepository::class,
            \Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository\EloquentLearnerSessionRepository::class,
        );

        $this->app->bind(
            \Src\SimExecution\Domain\RoleEnrolment\RoleEnrolmentRepository::class,
            \Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository\EloquentRoleEnrolmentRepository::class,
        );

        $this->app->bind(
            \Src\SimExecution\Domain\Sprint\LearnerSprintRepository::class,
            \Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository\EloquentLearnerSprintRepository::class,
        );

        $this->app->bind(
            \Src\SimExecution\Domain\Backlog\LearnerBacklogItemRepository::class,
            \Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository\EloquentLearnerBacklogItemRepository::class,
        );

        $this->app->bind(
            \Src\SimExecution\Domain\Board\SprintBoardEventRepository::class,
            \Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository\EloquentSprintBoardEventRepository::class,
        );

        $this->app->bind(
            \Src\SimExecution\Domain\Board\InjectedTaskCardRepository::class,
            \Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository\EloquentInjectedTaskCardRepository::class,
        );

        $this->app->bind(
            \Src\SimExecution\Domain\Diagnostic\DiagnosticSessionRepository::class,
            \Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository\EloquentDiagnosticSessionRepository::class,
        );
    }

    public function boot(): void
    {
        Route::middleware('web')->group(base_path('routes/learn.php'));
    }
}
