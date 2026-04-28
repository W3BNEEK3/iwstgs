<?php
use Illuminate\Support\Facades\Route;

Route::get('/', function (){
    return "Reporting module is alive";
})->name('index');