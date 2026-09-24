<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: alter_learner_sessions_add_current_sprint_fk
 *
 * Closes Reconciliation #4 from the learner_sessions migration: adds the FK
 * on current_sprint_id now that learner_sprints exists. nullOnDelete matches
 * current_scenario_id/current_task_id — a soft pointer, not ownership.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('learner_sessions', function (Blueprint $table) {
            $table->foreign('current_sprint_id')
                ->references('id')
                ->on('learner_sprints')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('learner_sessions', function (Blueprint $table) {
            $table->dropForeign(['current_sprint_id']);
        });
    }
};
