<?php
use Illuminate\Support\Facades\Route;
use Src\Competency\Presentation\Http\Controller\Admin\CompetenceDimensionController;

Route::get('competence-dimensions',           [CompetenceDimensionController::class, 'index'])->name('competence-dimensions.index');
Route::get('competence-dimensions/create',    [CompetenceDimensionController::class, 'create'])->name('competence-dimensions.create');
Route::post('competence-dimensions',          [CompetenceDimensionController::class, 'store'])->name('competence-dimensions.store');
