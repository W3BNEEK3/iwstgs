<?php

use Illuminate\Support\Facades\Route;
use Src\SimExecution\Presentation\Http\Controller\DiagnosticController;
use Src\SimExecution\Presentation\Http\Controller\LearnerEnrolmentController;
use Src\SimExecution\Presentation\Http\Controller\LearnerSettingsController;
use Src\SimExecution\Presentation\Http\Controller\ProjectCatalogueController;
use Src\SimExecution\Presentation\Http\Controller\ProjectEnrolmentController;
use Src\SimExecution\Presentation\Http\Controller\SessionOnboardingController;
use Src\SimExecution\Presentation\Http\Controller\SprintBoardController;
use Src\SimExecution\Presentation\Http\Controller\SprintPlanningController;
use Src\Submission\Presentation\Http\Controller\TaskSubmissionController;
use Src\EvalEngine\Presentation\Http\Controller\EvaluationResultController;

// Becoming a learner — one-time, independent of any specific project. Must be
// registered before the /learn/{project} wildcard group below, otherwise
// "enrol" would be swallowed as a {project} id.
Route::middleware('auth')->group(function () {
    Route::get('/learn/enrol', [LearnerEnrolmentController::class, 'show'])->name('learn.enrol');
    Route::post('/learn/enrol', [LearnerEnrolmentController::class, 'store']);
});

// Account settings — no feature flag, same as /learn/enrol; this is basic
// account functionality, not a simulation feature to gate.
Route::middleware(['auth', 'role:learner'])->group(function () {
    Route::get('/learn/settings', [LearnerSettingsController::class, 'show'])->name('learn.settings');
});

// Diagnostic pathway — once per learner, before the project catalogue.
Route::middleware(['auth', 'role:learner', 'feature:simulation.diagnostic_assessment'])->group(function () {
    Route::get('/learn/diagnostic', [DiagnosticController::class, 'show'])->name('learn.diagnostic');
});

// Project catalogue and per-project role enrolment.
Route::middleware(['auth', 'role:learner', 'diagnostic.complete', 'feature:simulation.project_catalogue'])->group(function () {
    Route::get('/learn', [ProjectCatalogueController::class, 'index'])->name('learn.catalogue');
    Route::get('/learn/{project}', [ProjectCatalogueController::class, 'show'])->name('learn.catalogue.show');
    Route::post('/learn/{project}/enrol', [ProjectEnrolmentController::class, 'store'])->name('learn.catalogue.enrol');
});

// Session onboarding — induction and first scenario briefing.
Route::middleware(['auth', 'role:learner', 'diagnostic.complete', 'feature:simulation.session_onboarding'])->group(function () {
    Route::get('/learn/{project}/onboarding', [SessionOnboardingController::class, 'show'])->name('learn.onboarding');
    Route::post('/learn/{project}/onboarding', [SessionOnboardingController::class, 'complete'])->name('learn.onboarding.complete');
});

// Sprint planning — backlog, sprint goal, confirmation.
Route::middleware(['auth', 'role:learner', 'diagnostic.complete', 'feature:simulation.sprint_planning'])->group(function () {
    Route::get('/learn/{project}/sprint-planning', [SprintPlanningController::class, 'show'])->name('learn.sprint-planning');
    Route::post('/learn/{project}/sprint-planning/goal', [SprintPlanningController::class, 'writeGoal'])->name('learn.sprint-planning.goal');
    Route::post('/learn/{project}/sprint-planning/items/{item}/move', [SprintPlanningController::class, 'moveItem'])->name('learn.sprint-planning.move-item');
    Route::post('/learn/{project}/sprint-planning/items/{item}/return', [SprintPlanningController::class, 'returnItem'])->name('learn.sprint-planning.return-item');
    Route::post('/learn/{project}/sprint-planning/confirm', [SprintPlanningController::class, 'confirm'])->name('learn.sprint-planning.confirm');
});

// Sprint board — Kanban view, item status, Artifact Vault and reference materials.
Route::middleware(['auth', 'role:learner', 'diagnostic.complete', 'feature:simulation.sprint_board'])->group(function () {
    Route::get('/learn/{project}/sprint-board', [SprintBoardController::class, 'show'])->name('learn.sprint-board');
    Route::post('/learn/{project}/sprint-board/items/{item}/status', [SprintBoardController::class, 'updateItemStatus'])->name('learn.sprint-board.update-status');
    Route::post('/learn/{project}/sprint-board/submit', [SprintBoardController::class, 'submit'])->name('learn.sprint-board.submit');
});

// Task submission — four-layer submission form, addressed by session + task rather than
// project, since a submission belongs to a specific in-progress task, not the project as a whole.
Route::middleware(['auth', 'role:learner', 'feature:simulation.task_submission'])->group(function () {
    Route::get('/learn/sessions/{session}/tasks/{task}/submit', [TaskSubmissionController::class, 'show'])->name('learn.task-submit');
    Route::post('/learn/sessions/{session}/tasks/{task}/submit', [TaskSubmissionController::class, 'store'])->name('learn.task-submit.store');
    Route::get('/learn/submissions/{submission}/evaluation', [EvaluationResultController::class, 'show'])->name('learn.evaluation-result');
});
