<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_habit_flags_table
 *
 * Raised by HabitPatternDetector when the same dimension stays below
 * proficient across 3+ recent evaluations. observation_count increments on
 * each repeat detection rather than creating a new row — one flag per
 * distinct habit description per learner.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('habit_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_id');
            $table->text('habit_description');
            $table->timestamp('first_observed_at')->useCurrent();
            $table->tinyInteger('observation_count')->unsigned()->default(1);
            $table->uuid('suggestion_task_id')->nullable();
            $table->boolean('is_resolved')->default(false);

            $table->foreign('learner_id')
                ->references('id')->on('learners')->cascadeOnDelete();
            $table->foreign('suggestion_task_id')
                ->references('id')->on('injected_task_cards')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_flags');
    }
};
