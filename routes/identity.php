<?php

use Illuminate\Support\Facades\Route;
use Src\Identity\Presentation\Http\Controller\LoginController;
use Src\Identity\Presentation\Http\Controller\RegistrationController;

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegistrationController::class, 'show'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store']);

    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
