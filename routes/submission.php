<?php
use Illuminate\Support\Facades\Route;

Route::get('/', function (){
    return "Submission module is alive";
})->name('index');