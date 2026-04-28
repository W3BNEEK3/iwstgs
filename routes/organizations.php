<?php
use Illuminate\Support\Facades\Route;

Route::get('/', function (){
    return "Organisations module is alive";
})->name('index');