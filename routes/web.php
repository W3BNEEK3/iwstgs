<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/dashboard', function () {
    /** @var \Src\Identity\Infrastructure\Persistence\Eloquent\Model\UserModel|null $user */
    $user = Illuminate\Support\Facades\Auth::user();
    
    if ($user && ($user->hasRole('super_admin') || $user->hasRole('content_author'))) {
        return redirect('/admin/projects');
    }
    
    return redirect('/learn');
})->middleware('auth')->name('dashboard');
