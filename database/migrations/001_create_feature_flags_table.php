<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* new class {}: this is php anonymous class introduced in php 7.0 */
/* a class that is intantiated without a name. */
/* I also realized that it does not have to be assigned to a variable, it can be returned directly. */
/* Instead declaring a class and then instantiating it you do both at the same time, with return new class extends Migration */
/* This was introduced in Laravel 8.37 to solve a massive headache for developers: Naming Collisions. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('flag_key', 200)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->string('module', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};
