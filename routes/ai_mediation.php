<?php

use Illuminate\Support\Facades\Route;
use Src\AIMediation\Presentation\Http\Controller\Admin\AiEngineController;
use Src\AIMediation\Presentation\Http\Controller\Admin\HumanReviewController;

Route::get('human-review',              [HumanReviewController::class, 'index'])->name('human-review.index');
Route::post('human-review/{review}/start',   [HumanReviewController::class, 'start'])->name('human-review.start');
Route::post('human-review/{review}/resolve', [HumanReviewController::class, 'resolve'])->name('human-review.resolve');

// AI Engine — Tiroco monitoring (Part 2 gap fixes)
Route::get('ai-engine',                          [AiEngineController::class, 'index'])->name('ai-engine.index');
Route::get('ai-engine/recommendations',           [AiEngineController::class, 'recommendations'])->name('ai-engine.recommendations');
Route::post('ai-engine/recommendations/{id}/approve', [AiEngineController::class, 'approveRecommendation'])->name('ai-engine.recommendations.approve');
Route::post('ai-engine/recommendations/{id}/dismiss', [AiEngineController::class, 'dismissRecommendation'])->name('ai-engine.recommendations.dismiss');
Route::get('ai-engine/config',                   [AiEngineController::class, 'config'])->name('ai-engine.config');
