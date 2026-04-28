<?php
use Illuminate\Support\Facades\Routes;

Route::get('/', function (){
    return "EvalEngine module is alive";
})->name('index');