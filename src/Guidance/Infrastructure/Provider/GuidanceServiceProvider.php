<?php
namespace Src\Guidance\Infrastructure\Provider;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\EvalEngine\Domain\Evaluation\EvaluationComplete;
use Src\Guidance\Application\Listener\QueueGuideMessagesOnEvaluation;
use Src\Guidance\Domain\Content\GuideContentRepository;
use Src\Guidance\Domain\GuidePreferenceRepository;
use Src\Guidance\Domain\Message\GuideMessageRepository;
use Src\Guidance\Infrastructure\Persistence\EloquentGuideContentRepository;
use Src\Guidance\Infrastructure\Persistence\EloquentGuideMessageRepository;
use Src\Guidance\Infrastructure\Persistence\EloquentGuidePreferenceRepository;
use Src\Guidance\Presentation\View\GuideComponent;

/**
 * Guidance bounded context: the in-app guide, Tiroco — authored per-page
 * walkthroughs, learner-aware messages (nudges, tips, outside resources,
 * feature announcements) and the per-project explainer shown before applying.
 */
class GuidanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GuidePreferenceRepository::class, EloquentGuidePreferenceRepository::class);
        $this->app->bind(GuideMessageRepository::class, EloquentGuideMessageRepository::class);
        $this->app->bind(GuideContentRepository::class, EloquentGuideContentRepository::class);
    }

    public function boot(): void
    {
        Route::middleware('web')->group(base_path('routes/guidance.php'));
        Route::middleware(['web', 'auth', 'role:content_author'])
            ->prefix('admin')
            ->name('admin.')
            ->group(base_path('routes/guidance_admin.php'));

        // Registered after EvalEngine's PostEvaluationRouter (providers boot in
        // order), so this evaluation's rank change and injected cards already exist.
        Event::listen(EvaluationComplete::class, QueueGuideMessagesOnEvaluation::class);
        Blade::component('guide', GuideComponent::class);
    }
}
