<?php

use Illuminate\Support\Facades\Route;
use Src\SimExecution\Presentation\Http\Controller\LearnerEnrolmentController;

// Learner enrolment — available to any authenticated user who has not yet enrolled
Route::get('/learn/enrol', [LearnerEnrolmentController::class, 'show'])->name('learn.enrol');
Route::post('/learn/enrol', [LearnerEnrolmentController::class, 'store']);

// Placeholder — replaced in Phase 5
Route::get('/learn/enrol/success', function () {
    return view('learn.enrol-success');
})->name('learn.enrol.success');

// Main learn dashboard — requires learner role (Phase 5+)
Route::middleware('role:learner')->group(function () {
    Route::get('/learn', function () {
        return view('learn.dashboard');
    })->name('learn.dashboard');
});
