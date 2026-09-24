<?php

use Illuminate\Support\Facades\Route;
use Src\Reporting\Presentation\Http\Controller\Admin\AdminReportingController;
use Src\Reporting\Presentation\Http\Controller\LearnerProfileController;

// Learner-facing competency profile — Implementation Plan §10.1/§10.2.
Route::middleware(['auth', 'role:learner', 'diagnostic.complete', 'feature:reporting.learner_profile'])->group(function () {
    Route::get('/learn/profile', [LearnerProfileController::class, 'show'])->name('learn.profile');
    Route::get('/learn/profile/sessions/{session}', [LearnerProfileController::class, 'session'])->name('learn.profile.session');
});

Route::middleware(['auth', 'role:learner', 'diagnostic.complete', 'feature:reporting.qualification_report'])->group(function () {
    Route::get('/learn/profile/qualification', [LearnerProfileController::class, 'qualification'])->name('learn.profile.qualification');
});

// Admin reporting — Implementation Plan §10.3.
Route::middleware(['auth', 'role:content_author', 'feature:reporting.admin_analytics'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/reporting/learners', [AdminReportingController::class, 'learners'])->name('reporting.learners');
        Route::get('/reporting/tasks', [AdminReportingController::class, 'tasks'])->name('reporting.tasks');
    });
