<?php
use Illuminate\Support\Facades\Route;

Route::get('/', function (){
    return "LearnerProfile module is alive";
})->name('index');