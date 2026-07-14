<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_diagnostic_sessions_table
 *
 * Records a learner's initial diagnostic flow (used to assign the starting rank).
 * FK dependency: learners only.
 *
 * Note: diagnostic_sessions is NOT the same as learner_sessions. A diagnostic is a
 * one-time assessment; once complete, the EvalEngine emits an initial_assignment rank_event
 * and the learner proceeds to a real learner_session. There is intentionally no FK between
 * diagnostic_sessions and rank_events — the rank_event references the learner directly.
 *
 * Delete behaviour:
 * - cascade on learner_id: diagnostic history dies with the learner.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('diagnostic_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_id');

            $table->enum('pathway', [
                'interview',
                'assessment_tasks',
                'diagnostic_scenario',
            ]);

            $table->enum('status', ['in_progress', 'complete', 'abandoned'])
                ->default('in_progress');

            // Populated once the diagnostic is complete and a rank is assigned
            $table->string('assigned_rank_tier', 20)->nullable();
            // #6 — smallint unsigned, nullable until assigned
            $table->smallInteger('assigned_rank_level')->unsigned()->nullable();

            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();

            $table->foreign('learner_id')
                ->references('id')
                ->on('learners')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_sessions');
    }
};
