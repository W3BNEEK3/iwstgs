<?php
use Illuminate\Support\Facades\Route;

Route::get('/', function (){
    return "Simulation module is alive";
})->name('index');