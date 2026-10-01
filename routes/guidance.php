<?php

use Illuminate\Support\Facades\Route;
use Src\Guidance\Presentation\Http\Controller\GuideController;
use Src\Guidance\Presentation\Http\Controller\GuideMessageController;

Route::middleware('auth')->prefix('guide')->name('guide.')->group(function () {
    Route::post('/steps/{step}/dismiss', [GuideController::class, 'dismiss'])->name('dismiss')->where('step', '[a-z0-9-]+');
    Route::post('/disable', [GuideController::class, 'disable'])->name('disable');
    Route::post('/enable', [GuideController::class, 'enable'])->name('enable');
    Route::post('/reset', [GuideController::class, 'reset'])->name('reset');

    Route::get('/next', [GuideMessageController::class, 'next'])->name('next');
    Route::post('/messages/{message}/dismiss', [GuideMessageController::class, 'dismiss'])->name('messages.dismiss')->whereUuid('message');
    Route::post('/messages/{message}/rate', [GuideMessageController::class, 'rate'])->name('messages.rate')->whereUuid('message');
    Route::post('/messages/{message}/opt-out', [GuideMessageController::class, 'optOut'])->name('messages.opt-out')->whereUuid('message');
    Route::post('/stuck', [GuideMessageController::class, 'stuck'])->name('stuck');
    Route::post('/kinds', [GuideMessageController::class, 'updateKinds'])->name('kinds');
    Route::get('/whats-new', [GuideMessageController::class, 'whatsNew'])->name('whats-new')->middleware('feature:guide.announcements');
});
