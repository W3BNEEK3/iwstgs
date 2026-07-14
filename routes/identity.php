<?php

use Illuminate\Support\Facades\Route;
use Src\Identity\Presentation\Http\Controller\LoginController;
use Src\Identity\Presentation\Http\Controller\RegistrationController;
use Src\Identity\Presentation\Http\Controller\SystemLoginController;

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegistrationController::class, 'show'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store']);

    // Organisation admin / content author login
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    // System administration portal (super_admin only — keep this route low-profile)
    Route::get('/sys/login', [SystemLoginController::class, 'show'])->name('sys.login');
    Route::post('/sys/login', [SystemLoginController::class, 'store'])->name('sys.login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::post('/sys/logout', [SystemLoginController::class, 'destroy'])->name('sys.logout');
});
