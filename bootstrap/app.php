<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Src\Identity\Presentation\Http\Middleware\RoleMiddleware::class,
            'feature' => \Src\Shared\Presentation\Http\Middleware\FeatureMiddleware::class,
            'diagnostic.complete' => \Src\SimExecution\Presentation\Http\Middleware\EnsureDiagnosticComplete::class,
        ]);

        // theme-toggle.js sets this cookie directly via document.cookie (plain
        // text, not through a Laravel response) so app.blade.php can read it
        // server-side before first paint. EncryptCookies otherwise tries to
        // decrypt it, fails silently on the unencrypted value, and the theme
        // never persists across a real page load.
        $middleware->encryptCookies(except: ['theme']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
