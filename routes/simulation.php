<?php
use Illuminate\Support\Facades\Route;
use Src\Simulation\Presentation\Http\Controller\Admin\ProjectController;
use Src\Simulation\Presentation\Http\Controller\Admin\ScenarioController;

Route::get('projects',            [ProjectController::class, 'index'])->name('projects.index');
Route::get('projects/create',     [ProjectController::class, 'create'])->name('projects.create');
Route::post('projects',           [ProjectController::class, 'store'])->name('projects.store');
Route::get('projects/{id}/edit',  [ProjectController::class, 'edit'])->name('projects.edit');
Route::patch('projects/{id}',     [ProjectController::class, 'update'])->name('projects.update');
Route::patch('projects/{id}/publish', [ProjectController::class, 'publish'])->name('projects.publish');

Route::get('projects/{project}/scenarios',              [ScenarioController::class, 'index'])->name('projects.scenarios.index');
Route::get('projects/{project}/scenarios/create',       [ScenarioController::class, 'create'])->name('projects.scenarios.create');
Route::post('projects/{project}/scenarios',             [ScenarioController::class, 'store'])->name('projects.scenarios.store');
Route::get('projects/{project}/scenarios/{id}/edit',    [ScenarioController::class, 'edit'])->name('projects.scenarios.edit');
Route::patch('projects/{project}/scenarios/{id}',       [ScenarioController::class, 'update'])->name('projects.scenarios.update');
Route::patch('projects/{project}/scenarios/{id}/publish',[ScenarioController::class, 'publish'])->name('projects.scenarios.publish');
// reference materials (HTMX)
Route::post('scenarios/{id}/materials',                 [ScenarioController::class, 'addMaterial'])->name('scenarios.materials.add');
Route::delete('scenarios/{id}/materials/{materialId}',  [ScenarioController::class, 'removeMaterial'])->name('scenarios.materials.remove');

