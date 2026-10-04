<?php

use Illuminate\Support\Facades\Route;
use Src\SourceControl\Presentation\Http\Controller\GitHubConnectController;
use Src\SourceControl\Presentation\Http\Controller\GitHubWebhookController;

// GitHub calls this; it is authenticated by the webhook signature, not a session (CSRF-exempt in bootstrap/app.php).
Route::post('/github/webhook', GitHubWebhookController::class)->name('github.webhook')
    ->middleware('feature:sourcecontrol.github');

Route::middleware(['auth', 'role:learner', 'feature:sourcecontrol.github'])->prefix('github')->name('github.')->group(function () {
    Route::get('/connect', [GitHubConnectController::class, 'connect'])->name('connect');
    Route::get('/callback', [GitHubConnectController::class, 'callback'])->name('callback');
    Route::get('/install', [GitHubConnectController::class, 'install'])->name('install');
    Route::post('/check', [GitHubConnectController::class, 'check'])->name('check');
    Route::post('/disconnect', [GitHubConnectController::class, 'disconnect'])->name('disconnect');
});
