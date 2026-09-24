<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_role_enrolments_table
 *
 * Records a learner's enrolment in a specific role on a specific project.
 * FK dependencies: learners, role_definitions, project_templates.
 *
 * Delete behaviours:
 * - cascadeOnDelete on learner_id: an enrolment has no meaning without its learner.
 * - restrictOnDelete on role_id / project_id: you should not be able to delete a
 *   role definition or project that learners are enrolled in — preserves referential history.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('role_enrolments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_id');
            $table->uuid('role_id');
            $table->uuid('project_id');

            // true = the learner passed the experience gate (years_experience >= role.min_years_experience)
            $table->boolean('is_gated')->default(false);

            $table->timestamp('enrolled_at')->useCurrent();

            $table->foreign('learner_id')
                ->references('id')
                ->on('learners')
                ->cascadeOnDelete();

            $table->foreign('role_id')
                ->references('id')
                ->on('role_definitions')
                ->restrictOnDelete();

            $table->foreign('project_id')
                ->references('id')
                ->on('project_templates')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_enrolments');
    }
};
