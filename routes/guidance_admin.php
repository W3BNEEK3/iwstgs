<?php

use Illuminate\Support\Facades\Route;
use Src\Guidance\Presentation\Http\Controller\Admin\GuideAdminController;

// Admin → Guide: Tiroco's triggers and health, tips, outside resources, announcements.
Route::prefix('guide')->name('guide.')->group(function () {
    Route::get('/', [GuideAdminController::class, 'index'])->name('index');
    Route::put('/settings', [GuideAdminController::class, 'updateSettings'])->name('settings');

    Route::get('/tips', [GuideAdminController::class, 'tips'])->name('tips');
    Route::post('/tips', [GuideAdminController::class, 'storeTip'])->name('tips.store');
    Route::put('/tips/{tip}', [GuideAdminController::class, 'updateTip'])->name('tips.update')->whereUuid('tip');
    Route::delete('/tips/{tip}', [GuideAdminController::class, 'destroyTip'])->name('tips.destroy')->whereUuid('tip');

    Route::get('/resources', [GuideAdminController::class, 'resources'])->name('resources');
    Route::get('/resources/new', [GuideAdminController::class, 'createResource'])->name('resources.create');
    Route::post('/resources', [GuideAdminController::class, 'storeResource'])->name('resources.store');
    Route::get('/resources/{resource}/edit', [GuideAdminController::class, 'editResource'])->name('resources.edit')->whereUuid('resource');
    Route::put('/resources/{resource}', [GuideAdminController::class, 'updateResource'])->name('resources.update')->whereUuid('resource');
    Route::delete('/resources/{resource}', [GuideAdminController::class, 'destroyResource'])->name('resources.destroy')->whereUuid('resource');
    Route::post('/resources/check-links', [GuideAdminController::class, 'checkLinks'])->name('resources.check');

    Route::get('/announcements', [GuideAdminController::class, 'announcements'])->name('announcements');
    Route::get('/announcements/new', [GuideAdminController::class, 'createAnnouncement'])->name('announcements.create');
    Route::post('/announcements', [GuideAdminController::class, 'storeAnnouncement'])->name('announcements.store');
    Route::get('/announcements/{announcement}/edit', [GuideAdminController::class, 'editAnnouncement'])->name('announcements.edit')->whereUuid('announcement');
    Route::put('/announcements/{announcement}', [GuideAdminController::class, 'updateAnnouncement'])->name('announcements.update')->whereUuid('announcement');
    Route::post('/announcements/{announcement}/generate', [GuideAdminController::class, 'generateAnnouncement'])->name('announcements.generate')->whereUuid('announcement');
    Route::post('/announcements/{announcement}/status', [GuideAdminController::class, 'announcementStatus'])->name('announcements.status')->whereUuid('announcement');
});
