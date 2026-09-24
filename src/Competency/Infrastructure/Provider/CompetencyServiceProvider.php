<?php

namespace Src\Competency\Infrastructure\Provider;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Competency bounded context service provider.
 *
 * Owns: CompetenceDimension, RoleDefinition. CompetenceDimension started as
 * seed-only static data, but the admin competence-dimensions screen now
 * lets content authors add new dimensions at runtime — RoleDefinition is
 * still seed-only.
 */
class CompetencyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Src\Competency\Domain\Dimension\CompetenceDimensionRepository::class,
            \Src\Competency\Infrastructure\Persistence\Eloquent\Repository\EloquentCompetenceDimensionRepository::class,
        );

        $this->app->bind(
            \Src\Competency\Domain\Role\RoleDefinitionRepository::class,
            \Src\Competency\Infrastructure\Persistence\Eloquent\Repository\EloquentRoleDefinitionRepository::class,
        );
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth', 'role:content_author'])
            ->prefix('admin')
            ->name('admin.')
            ->group(base_path('routes/competency.php'));
    }
}
