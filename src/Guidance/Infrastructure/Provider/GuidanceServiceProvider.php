<?php
namespace Src\Guidance\Infrastructure\Provider;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Guidance\Domain\GuidePreferenceRepository;
use Src\Guidance\Infrastructure\Persistence\EloquentGuidePreferenceRepository;
use Src\Guidance\Presentation\View\GuideComponent;

/**
 * Guidance bounded context: the in-app guide (per-page tips, milestone tips,
 * on/off preference) and the per-project explainer shown before applying.
 */
class GuidanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GuidePreferenceRepository::class, EloquentGuidePreferenceRepository::class);
    }

    public function boot(): void
    {
        Route::middleware('web')->group(base_path('routes/guidance.php'));
        Blade::component('guide', GuideComponent::class);
    }
}
