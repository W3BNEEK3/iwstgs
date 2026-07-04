<?php
use Illuminate\Support\Facades\Route;
use Src\Simulation\Presentation\Http\Controller\Admin\ProjectController;
use Src\Simulation\Presentation\Http\Controller\Admin\ScenarioController;
use Src\Simulation\Presentation\Http\Controller\Admin\TaskController;

Route::get('projects',            [ProjectController::class, 'index'])->name('projects.index');
Route::get('projects/create',     [ProjectController::class, 'create'])->name('projects.create');
Route::post('projects',           [ProjectController::class, 'store'])->name('projects.store');
Route::get('projects/{id}/edit',  [ProjectController::class, 'edit'])->name('projects.edit');
Route::patch('projects/{id}',     [ProjectController::class, 'update'])->name('projects.update');
Route::patch('projects/{id}/publish', [ProjectController::class, 'publish'])->name('projects.publish');

// --- Vault Items (nested under project) ---
use Src\Simulation\Presentation\Http\Controller\Admin\VaultItemController;
Route::get('projects/{project}/vault',            [VaultItemController::class, 'index'])->name('projects.vault.index');
Route::post('projects/{project}/vault',           [VaultItemController::class, 'store'])->name('projects.vault.store');
Route::delete('projects/{project}/vault/{item}',  [VaultItemController::class, 'destroy'])->name('projects.vault.destroy');

Route::get('projects/{project}/scenarios',              [ScenarioController::class, 'index'])->name('projects.scenarios.index');
Route::get('projects/{project}/scenarios/create',       [ScenarioController::class, 'create'])->name('projects.scenarios.create');
Route::post('projects/{project}/scenarios',             [ScenarioController::class, 'store'])->name('projects.scenarios.store');
Route::get('projects/{project}/scenarios/{id}/edit',    [ScenarioController::class, 'edit'])->name('projects.scenarios.edit');
Route::patch('projects/{project}/scenarios/{id}',       [ScenarioController::class, 'update'])->name('projects.scenarios.update');
Route::patch('projects/{project}/scenarios/{id}/publish',[ScenarioController::class, 'publish'])->name('projects.scenarios.publish');
// reference materials (HTMX)
Route::post('scenarios/{id}/materials',                 [ScenarioController::class, 'addMaterial'])->name('scenarios.materials.add');
Route::delete('scenarios/{id}/materials/{materialId}',  [ScenarioController::class, 'removeMaterial'])->name('scenarios.materials.remove');

// --- Tasks (nested under scenario) ---
Route::get('scenarios/{scenario}/tasks',                [TaskController::class, 'index'])->name('scenarios.tasks.index');
Route::get('scenarios/{scenario}/tasks/create',         [TaskController::class, 'create'])->name('scenarios.tasks.create');
Route::post('scenarios/{scenario}/tasks',               [TaskController::class, 'store'])->name('scenarios.tasks.store');
Route::get('scenarios/{scenario}/tasks/{id}/edit',      [TaskController::class, 'edit'])->name('scenarios.tasks.edit');
Route::patch('scenarios/{scenario}/tasks/{id}',         [TaskController::class, 'update'])->name('scenarios.tasks.update');
Route::patch('scenarios/{scenario}/tasks/{id}/publish', [TaskController::class, 'publish'])->name('scenarios.tasks.publish');
// HTMX child add endpoints
Route::post('tasks/{id}/deliverables',                  [TaskController::class, 'addDeliverable'])->name('tasks.deliverables.add');
Route::post('tasks/{id}/cac-variants',                  [TaskController::class, 'addCacVariant'])->name('tasks.cac-variants.add');
Route::post('tasks/{id}/dependencies',                  [TaskController::class, 'addDependency'])->name('tasks.dependencies.add');
Route::post('tasks/{id}/anchors',                       [TaskController::class, 'addKnowledgeAnchor'])->name('tasks.anchors.add');
Route::post('tasks/{id}/prompts',                       [TaskController::class, 'addGuidancePrompt'])->name('tasks.prompts.add');
// HTMX shared child remove
Route::delete('tasks/{id}/children/{collection}/{childId}', [TaskController::class, 'removeChild'])->name('tasks.children.remove');

// --- Rubric Criteria (nested under tasks) ---
use Src\Simulation\Presentation\Http\Controller\Admin\RubricCriterionController;

Route::get('tasks/{task}/criteria',            [RubricCriterionController::class, 'index'])->name('tasks.criteria.index');
Route::get('tasks/{task}/criteria/create',     [RubricCriterionController::class, 'create'])->name('tasks.criteria.create');
Route::post('tasks/{task}/criteria',           [RubricCriterionController::class, 'store'])->name('tasks.criteria.store');
Route::get('tasks/{task}/criteria/{id}/edit',  [RubricCriterionController::class, 'edit'])->name('tasks.criteria.edit');
Route::patch('criteria/{id}',                  [RubricCriterionController::class, 'update'])->name('criteria.update');
Route::delete('criteria/{id}',                 [RubricCriterionController::class, 'destroy'])->name('criteria.destroy');
