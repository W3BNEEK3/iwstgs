<?php

use Illuminate\Support\Facades\Route;
use Src\Guidance\Presentation\Http\Controller\GuideController;

Route::middleware('auth')->prefix('guide')->name('guide.')->group(function () {
    Route::post('/steps/{step}/dismiss', [GuideController::class, 'dismiss'])->name('dismiss')->where('step', '[a-z0-9-]+');
    Route::post('/disable', [GuideController::class, 'disable'])->name('disable');
    Route::post('/enable', [GuideController::class, 'enable'])->name('enable');
    Route::post('/reset', [GuideController::class, 'reset'])->name('reset');
});
