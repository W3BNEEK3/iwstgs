<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_sprint_board_events_table
 *
 * Immutable, append-only audit log of every sprint-board interaction —
 * created_at only, same shape as rank_events. sprint_id/item_id are
 * nullOnDelete: the audit trail is allowed to outlive the specific sprint
 * or item it describes, same reasoning rank_events.source_session_id used.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('sprint_board_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_id');
            $table->uuid('session_id');
            $table->uuid('sprint_id')->nullable();
            $table->uuid('item_id')->nullable();

            $table->enum('event_type', [
                'item_moved_to_sprint',
                'item_status_changed',
                'priority_changed',
                'sprint_goal_written',
                'sprint_confirmed',
                'sprint_submitted',
            ]);
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('learner_id')
                ->references('id')->on('learners')->cascadeOnDelete();
            $table->foreign('session_id')
                ->references('id')->on('learner_sessions')->cascadeOnDelete();
            $table->foreign('sprint_id')
                ->references('id')->on('learner_sprints')->nullOnDelete();
            $table->foreign('item_id')
                ->references('id')->on('learner_backlog_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sprint_board_events');
    }
};
