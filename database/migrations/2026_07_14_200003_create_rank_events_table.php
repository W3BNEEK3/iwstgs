<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_rank_events_table
 *
 * Immutable audit log of every rank change for a learner.
 * FK dependencies: learners, learner_sessions (nullable).
 *
 * Reconciliation #2: source_session_id is NULLABLE.
 * Reason: the very first event (event_type = 'initial_assignment') is emitted at the end of
 * the diagnostic session — before any learner_session row exists. Making this NOT NULL would
 * require creating a dummy session, which is semantically wrong. Deviation from Data Model v2
 * is intentional and documented.
 *
 * This table is created AFTER learner_sessions so the nullable FK can reference it.
 * Only created_at (no updated_at) — rank events are immutable; rows are never updated.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('rank_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_id');

            $table->enum('event_type', [
                'initial_assignment',
                'escalation',
                'de_escalation',
                'sub_level_progression',
                'review_triggered',
            ]);

            // null on initial_assignment (no prior rank exists)
            $table->string('from_rank_tier', 20)->nullable();
            // #6 — smallint unsigned, nullable for initial_assignment
            $table->smallInteger('from_rank_level')->unsigned()->nullable();

            $table->string('to_rank_tier', 20);
            // #6 — smallint unsigned
            $table->smallInteger('to_rank_level')->unsigned();

            // Human-readable explanation: "Three consecutive proficient scores in dim_implementation"
            $table->text('trigger_reason');

            // #2 — NULLABLE (initial_assignment has no session yet)
            $table->uuid('source_session_id')->nullable();

            // Immutable audit log — only created_at, no updated_at
            $table->timestamp('created_at')->useCurrent();

            // Foreign keys
            $table->foreign('learner_id')
                ->references('id')
                ->on('learners')
                ->cascadeOnDelete();

            $table->foreign('source_session_id')
                ->references('id')
                ->on('learner_sessions')
                ->nullOnDelete(); // if session is deleted, event stays but loses its session link
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rank_events');
    }
};
