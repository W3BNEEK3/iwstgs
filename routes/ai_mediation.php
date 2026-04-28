<?php
use Illuminate\Support\Facades\Route;

Route::get('/', function (){
    return "AIMediation is alive";
})->name('index');