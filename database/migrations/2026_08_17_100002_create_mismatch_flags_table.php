<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_mismatch_flags_table
 *
 * Raised by MismatchDetector when a submission's quality significantly
 * exceeds (underestimation) the learner's current rank — the failure-streak
 * side of a mismatch (overestimation) is LearnerProfile.mismatch_flag_active,
 * already handled in Phase 8 via RecordEvaluationOutcome; this table is for
 * the escalation-prompt flow specifically.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('mismatch_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_id');

            $table->enum('mismatch_type', ['underestimation', 'overestimation']);
            $table->timestamp('detected_at')->useCurrent();
            $table->boolean('prompt_issued')->default(false);
            $table->enum('learner_response', ['accepted_escalation', 'declined_escalation'])->nullable();
            $table->enum('resolution', ['auto_escalated', 'gradual_escalation', 'rank_review_triggered', 'unresolved'])->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->foreign('learner_id')
                ->references('id')->on('learners')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mismatch_flags');
    }
};
