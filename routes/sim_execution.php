<?php
use Illuminate\Support\Facades\Route;

Route::get('/', function (){
    return "SIM_Execution is alive";
})->name('index');