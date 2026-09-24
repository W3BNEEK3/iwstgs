<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_gap_flags_table
 *
 * Raised by ConsequenceTaskInjector on a failing evaluation, one per
 * failing dimension. Resolved later (is_resolved) once the learner passes
 * a follow-up task targeting that dimension — a real small mutation, not a
 * write-once byproduct, but simple enough not to need a full aggregate
 * (same treatment as dimension_scores/concept_mastery_records in Phase 8).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('gap_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_id');
            $table->string('dimension_id', 20);
            $table->uuid('sub_criterion_id')->nullable();
            $table->uuid('source_task_id');
            $table->uuid('source_session_id');

            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('learner_id')
                ->references('id')->on('learners')->cascadeOnDelete();
            $table->foreign('dimension_id')
                ->references('id')->on('competence_dimensions')->restrictOnDelete();
            $table->foreign('sub_criterion_id')
                ->references('id')->on('rubric_criteria')->nullOnDelete();
            $table->foreign('source_task_id')
                ->references('id')->on('tasks')->restrictOnDelete();
            $table->foreign('source_session_id')
                ->references('id')->on('learner_sessions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gap_flags');
    }
};
