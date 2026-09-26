<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Some databases have the August ALTER migrations recorded as run while the
 * columns they add are missing (e.g. a migration was recorded before its file
 * gained the column), and `migrate` then reports "Nothing to migrate" while
 * inserts fail with "Unknown column". This re-applies each of those columns
 * only where it is absent, so it is a no-op on a healthy database.
 */
return new class extends Migration {
    public function up(): void
    {
        $this->addIfMissing('habit_flags', 'source_task_id', function (Blueprint $table) {
            $table->uuid('source_task_id')->nullable()->after('habit_description');
            $table->foreign('source_task_id')->references('id')->on('tasks')->nullOnDelete();
        });

        $this->addIfMissing('project_templates', 'onboarding_briefing', function (Blueprint $table) {
            $table->text('onboarding_briefing')->nullable()->after('coding_guidelines');
        });

        $this->addIfMissing('learner_profiles', 'success_streak', function (Blueprint $table) {
            $table->unsignedInteger('success_streak')->default(0)->after('failure_streak');
        });

        $this->addIfMissing('learner_profiles', 'final_competency_snapshot', function (Blueprint $table) {
            $table->json('final_competency_snapshot')->nullable()->after('success_streak');
        });

        $this->addIfMissing('learner_sessions', 'targeted_dimension_id', function (Blueprint $table) {
            $table->uuid('targeted_dimension_id')->nullable()->after('current_scenario_id');
            $table->foreign('targeted_dimension_id')->references('id')->on('competence_dimensions')->nullOnDelete();
        });

        $this->addIfMissing('evaluation_results', 'follow_up_prompt_text', function (Blueprint $table) {
            $table->text('follow_up_prompt_text')->nullable()->after('is_uncertain');
        });

        $this->addIfMissing('evaluation_results', 'follow_up_status', function (Blueprint $table) {
            $table->enum('follow_up_status', ['none', 'pending', 'answered'])->default('none')->after('follow_up_prompt_text');
        });
    }

    public function down(): void
    {
        // Nothing to undo: the columns belong to the original August migrations.
    }

    private function addIfMissing(string $table, string $column, Closure $definition): void
    {
        if (Schema::hasTable($table) && ! Schema::hasColumn($table, $column)) {
            Schema::table($table, $definition);
        }
    }
};
