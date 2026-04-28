<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function (){
    return "Competency Module is alive";
})->name('index');