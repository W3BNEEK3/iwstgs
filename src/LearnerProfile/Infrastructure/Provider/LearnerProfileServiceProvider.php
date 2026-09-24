<?php

namespace Src\LearnerProfile\Infrastructure\Provider;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Src\LearnerProfile\Domain\Profile\LearnerProfileRepository;
use Src\LearnerProfile\Infrastructure\Listener\BootstrapLearnerProfileOnEnrolment;
use Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Repository\EloquentLearnerProfileRepository;
use Src\SimExecution\Domain\Enrollment\LearnerEnrolled;

class LearnerProfileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LearnerProfileRepository::class, EloquentLearnerProfileRepository::class);

        $this->app->bind(
            \Src\LearnerProfile\Domain\Rank\RankEventRepository::class,
            \Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Repository\EloquentRankEventRepository::class,
        );

        $this->app->bind(
            \Src\LearnerProfile\Domain\Profile\DimensionScoreRepository::class,
            \Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Repository\EloquentDimensionScoreRepository::class,
        );

        $this->app->bind(
            \Src\LearnerProfile\Domain\ConceptMastery\ConceptMasteryRepository::class,
            \Src\LearnerProfile\Infrastructure\Persistence\Eloquent\Repository\EloquentConceptMasteryRepository::class,
        );
    }

    public function boot(): void
    {
        Event::listen(LearnerEnrolled::class, BootstrapLearnerProfileOnEnrolment::class);
    }
}
