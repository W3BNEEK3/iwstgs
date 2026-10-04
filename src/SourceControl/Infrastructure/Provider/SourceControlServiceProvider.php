<?php
namespace Src\SourceControl\Infrastructure\Provider;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\EvalEngine\Domain\Evaluation\EvaluationComplete;
use Src\SourceControl\Application\Listener\AcceptCommitOnPass;
use Src\SourceControl\Domain\RepositoryHost;
use Src\SourceControl\Domain\SourceControlRepository;
use Src\SourceControl\Infrastructure\GitHub\GitHubAppAuth;
use Src\SourceControl\Infrastructure\GitHub\GitHubRepositoryHost;
use Src\SourceControl\Infrastructure\GitHub\WebhookSignatureVerifier;
use Src\SourceControl\Infrastructure\Persistence\DbSourceControlRepository;

/**
 * SourceControl bounded context (design doc v2-01 §3.3): the learner's
 * GitHub account and repositories, CI results and webhooks. GitHub specifics
 * stay here; other modules use its queries and commands.
 */
class SourceControlServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SourceControlRepository::class, DbSourceControlRepository::class);
        $this->app->singleton(GitHubAppAuth::class, fn () => new GitHubAppAuth(config('services.github', [])));
        $this->app->bind(RepositoryHost::class, fn ($app) => new GitHubRepositoryHost($app->make(GitHubAppAuth::class), config('services.github', [])));
        $this->app->bind(WebhookSignatureVerifier::class, fn () => new WebhookSignatureVerifier(config('services.github.webhook_secret')));
    }

    public function boot(): void
    {
        Route::middleware('web')->group(base_path('routes/source_control.php'));
        Event::listen(EvaluationComplete::class, AcceptCommitOnPass::class);
    }
}
