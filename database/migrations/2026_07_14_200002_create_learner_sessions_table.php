<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_learner_sessions_table
 *
 * A single session of a learner working through a project.
 * FK dependencies: learners, project_templates, role_enrolments, scenario_templates, tasks.
 *
 * Reconciliation #4: current_sprint_id is a plain nullable uuid with NO FK yet.
 * The FK to learner_sprints will be added in Phase 6 once that table exists.
 *
 * Delete behaviours:
 * - cascade on learner_id: session dies with the learner.
 * - cascade on role_enrolment_id: session is derived from the enrolment.
 * - restrict on project_id: can't delete a project while learners have active sessions in it.
 * - nullOnDelete on current_scenario_id / current_task_id: soft pointers; losing the content
 *   doesn't destroy the session itself (the session can be migrated or marked suspended).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('learner_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_id');
            $table->uuid('project_id');
            $table->uuid('role_enrolment_id');

            $table->enum('status', ['diagnostic', 'active', 'complete', 'suspended'])
                ->default('active');

            // Soft pointer to the current position — null if at the very start or end
            $table->uuid('current_scenario_id')->nullable();
            $table->uuid('current_task_id')->nullable();

            // #4 — FK deferred to Phase 6 when learner_sprints exists
            $table->uuid('current_sprint_id')->nullable();

            // Timestamps
            $table->timestamp('induction_completed_at')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();

            // Foreign keys
            $table->foreign('learner_id')
                ->references('id')
                ->on('learners')
                ->cascadeOnDelete();

            $table->foreign('project_id')
                ->references('id')
                ->on('project_templates')
                ->restrictOnDelete();

            $table->foreign('role_enrolment_id')
                ->references('id')
                ->on('role_enrolments')
                ->cascadeOnDelete();

            $table->foreign('current_scenario_id')
                ->references('id')
                ->on('scenario_templates')
                ->nullOnDelete();

            $table->foreign('current_task_id')
                ->references('id')
                ->on('tasks')
                ->nullOnDelete();

            // current_sprint_id FK added in Phase 6 (learner_sprints not yet created)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learner_sessions');
    }
};
