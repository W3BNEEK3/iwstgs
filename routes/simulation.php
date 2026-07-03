<?php
use Illuminate\Support\Facades\Route;
use Src\Simulation\Presentation\Http\Controller\Admin\ProjectController;

Route::get('projects',            [ProjectController::class, 'index'])->name('projects.index');
Route::get('projects/create',     [ProjectController::class, 'create'])->name('projects.create');
Route::post('projects',           [ProjectController::class, 'store'])->name('projects.store');
Route::get('projects/{id}/edit',  [ProjectController::class, 'edit'])->name('projects.edit');
Route::patch('projects/{id}',     [ProjectController::class, 'update'])->name('projects.update');
Route::patch('projects/{id}/publish', [ProjectController::class, 'publish'])->name('projects.publish');
