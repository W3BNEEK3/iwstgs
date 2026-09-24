<?php
use Illuminate\Support\Facades\Route;
use Src\Shared\Presentation\Http\Controller\Admin\FeatureFlagController;

Route::get('feature-flags',                [FeatureFlagController::class, 'index'])->name('feature-flags.index');
Route::patch('feature-flags/{key}/toggle', [FeatureFlagController::class, 'toggle'])->name('feature-flags.toggle');
