<?php

namespace Src\Submission\Infrastructure\Provider;

use Illuminate\Support\ServiceProvider;

/**
 * Submission bounded context service provider.
 *
 * Owns: SubmissionPackage, SubmissionArtifact.
 */
class SubmissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Src\Submission\Domain\Submission\SubmissionPackageRepository::class,
            \Src\Submission\Infrastructure\Persistence\Eloquent\Repository\EloquentSubmissionPackageRepository::class,
        );

        $this->app->bind(
            \Src\Submission\Domain\Submission\SubmissionArtifactRepository::class,
            \Src\Submission\Infrastructure\Persistence\Eloquent\Repository\EloquentSubmissionArtifactRepository::class,
        );
    }

    public function boot(): void
    {
        // Submission routes live in routes/learn.php (registered by
        // SimExecutionServiceProvider) — they're /learn/... URLs, and that
        // file is already loaded once; loading it again here would collide.
    }
}
