<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 001 — Feature Flags table.
 *
 * Why first? Every subsequent phase deploys features behind a flag. The flag
 * table must exist before any feature-gated code can run. Seeding flags as
 * disabled lets us deploy dead code safely and turn things on deliberately.
 *
 * Naming convention: 001_, 002_, ... ensures alphabetical = execution order,
 * which is how Laravel resolves migration ordering.
 */
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
