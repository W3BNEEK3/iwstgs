<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_learner_sprints_table
 *
 * One sprint within a learner_session. learner_id is duplicated alongside
 * learner_session_id (both point to the same ownership chain) so sprint
 * queries scoped by learner don't need to join through learner_sessions —
 * the same denormalization already used on learner_backlog_items and
 * sprint_board_events.
 *
 * sprint_goal_source and the rank-differentiated sprint goal interaction
 * (pre-written / templated / blank) are Business Logic Doc §15 —
 * implemented in the Application layer, not enforced by this schema.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('learner_sprints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_session_id');
            $table->uuid('learner_id');
            $table->uuid('project_id');

            $table->smallInteger('sprint_number')->unsigned();
            $table->text('sprint_goal')->nullable();
            $table->enum('sprint_goal_source', [
                'system_defined',
                'learner_defined',
                'learner_completed_template',
            ])->default('system_defined');

            $table->enum('status', ['planning', 'active', 'submitted', 'evaluated'])
                ->default('planning');

            $table->boolean('scope_warning_issued')->default(false);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->foreign('learner_session_id')
                ->references('id')->on('learner_sessions')->cascadeOnDelete();
            $table->foreign('learner_id')
                ->references('id')->on('learners')->cascadeOnDelete();
            $table->foreign('project_id')
                ->references('id')->on('project_templates')->restrictOnDelete();

            $table->unique(['learner_session_id', 'sprint_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learner_sprints');
    }
};
