<?php
namespace Src\Simulation\Infrastructure\Provider;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Simulation\Domain\Project\ProjectTemplateRepository;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Repository\EloquentProjectTemplateRepository;

class SimulationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProjectTemplateRepository::class, EloquentProjectTemplateRepository::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth', 'role:content_author'])
            ->prefix('admin')
            ->name('admin.')
            ->group(base_path('routes/simulation.php'));
    }
}
