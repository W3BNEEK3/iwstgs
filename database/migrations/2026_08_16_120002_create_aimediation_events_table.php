<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_aimediation_events_table
 *
 * Immutable audit log of every AI-mediation trigger/action pair — created_at
 * only, same convention as rank_events/sprint_board_events. trigger_source_id
 * is deliberately FK-less: depending on trigger_type it points at a
 * submission, an evaluation, a task, or nothing at all, so it can't be a
 * single-table foreign key (a polymorphic reference recorded for audit
 * purposes, not a relationship Eloquent needs to traverse).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('aimediation_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_id');
            $table->uuid('session_id');

            $table->enum('trigger_type', [
                'submission_received', 'failure_detected', 'uncertain_evaluation',
                'mismatch_detected', 'habit_detected', 'dimension_weakness',
                'failure_threshold_exceeded', 'scenario_complete', 'knowledge_anchor_partial',
            ]);
            $table->uuid('trigger_source_id')->nullable();

            $table->enum('action_taken', [
                'task_completed', 'consequence_injected', 'suggestion_queued',
                'follow_up_prompt_issued', 'rank_review_triggered', 'mismatch_flagged',
                'human_review_queued', 'dimension_targeted_next_scenario',
                'knowledge_anchor_hint_issued', 'no_action',
            ]);
            $table->json('action_detail')->nullable();
            $table->boolean('is_deterministic')->default(true);
            $table->decimal('confidence_score', 4, 3)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('learner_id')
                ->references('id')->on('learners')->cascadeOnDelete();
            $table->foreign('session_id')
                ->references('id')->on('learner_sessions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aimediation_events');
    }
};
