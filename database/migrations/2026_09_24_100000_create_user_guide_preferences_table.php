<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user state for the in-app guide (Guidance module): whether the guide
 * pops up at all, and which guide steps the user has already dismissed. A
 * missing row means "guide on, nothing dismissed".
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_guide_preferences', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->boolean('is_enabled')->default(true);
            $table->json('dismissed_steps')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_guide_preferences');
    }
};
