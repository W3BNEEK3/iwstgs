<?php
use Illuminate\Support\Facades\Route;
use Src\EvalEngine\Presentation\Http\Controller\FollowUpPromptController;

/**
 * Gap 2 — learner-facing follow-up prompt answer.
 * Auth middleware is applied at the route group level in RouteServiceProvider.
 */
Route::post('submissions/{submissionId}/follow-up', [FollowUpPromptController::class, 'store'])
    ->name('submissions.follow-up.store');