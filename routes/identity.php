<?php
use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return "Identity module is alive";
})->name('login');