<?php

namespace Src\Identity\Infrastructure\Provider;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Identity\Domain\Auth\AuthenticationService;
use Src\Identity\Domain\User\UserRepository;
use Src\Identity\Infrastructure\Auth\LaravelAuthService;
use Src\Identity\Infrastructure\Listener\GrantLearnerRoleOnEnrolment;
use Src\Identity\Infrastructure\Persistence\Eloquent\Repository\EloquentUserRepository;
use Src\SimExecution\Domain\Enrollment\LearnerEnrolled;

class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(AuthenticationService::class, LaravelAuthService::class);
    }

    public function boot(): void
    {
        Route::middleware('web')->group(base_path('routes/identity.php'));

        Event::listen(LearnerEnrolled::class, GrantLearnerRoleOnEnrolment::class);
    }
}