<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_learner_backlog_items_table
 *
 * A learner's own copy of a backlog_item_template, tracked through the
 * board's status lifecycle (backlog -> in_sprint -> in_progress -> done/blocked).
 *
 * injected_card_id has no FK here — injected_task_cards doesn't exist until
 * the next migration in this phase. The FK is added by a follow-up alter
 * migration once that table exists, same deferred-FK discipline Phase 5a
 * used for learner_sessions.current_sprint_id.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('learner_backlog_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_session_id');
            $table->uuid('learner_id');
            $table->uuid('template_item_id');
            $table->uuid('sprint_id')->nullable();

            $table->enum('status', ['backlog', 'in_sprint', 'in_progress', 'done', 'blocked'])
                ->default('backlog');
            $table->enum('priority', ['must_have', 'should_have', 'could_have', 'wont_have']);

            $table->timestamp('moved_to_sprint_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('learner_notes')->nullable();

            $table->boolean('is_injected')->default(false);
            $table->uuid('injected_card_id')->nullable(); // FK added in a later migration, see docblock

            $table->foreign('learner_session_id')
                ->references('id')->on('learner_sessions')->cascadeOnDelete();
            $table->foreign('learner_id')
                ->references('id')->on('learners')->cascadeOnDelete();
            $table->foreign('template_item_id')
                ->references('id')->on('backlog_item_templates')->restrictOnDelete();
            $table->foreign('sprint_id')
                ->references('id')->on('learner_sprints')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learner_backlog_items');
    }
};
