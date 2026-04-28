<?php

namespace Src\Content\Infrastructure\Provider;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Content bounded context service provider.
 *
 * Phase 4 will add:
 *  - ProjectRepository, ScenarioRepository bindings
 *  - Admin route group with role:content_author middleware
 */
class ContentServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Route::middleware('web')
            ->prefix('admin/content')
            ->name('admin.content.')
            ->group(base_path('routes/content.php'));
    }
}
