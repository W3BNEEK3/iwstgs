<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_submission_packages_table
 *
 * One row per submission attempt — write-once, never updated by the learner
 * (same byproduct reasoning as role_enrolments/rank_events: no domain
 * aggregate, just a repository that inserts inside SubmitTaskHandler's
 * transaction). Phase 8 (EvalEngine) will read these rows to grade; it
 * writes its results to its own tables, not back onto this one.
 *
 * sprint_id/task_id/scenario_id are restrictOnDelete — a submission is a
 * permanent record and content/planning rows it references shouldn't be
 * deletable out from under it, same discipline as project_id elsewhere.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('submission_packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_session_id');
            $table->uuid('learner_id');
            $table->uuid('task_id');
            $table->uuid('scenario_id');
            $table->uuid('sprint_id');

            $table->tinyInteger('attempt_number')->unsigned()->default(1);

            $table->text('layer1_text')->nullable();
            $table->json('layer2_artifact_ids')->nullable();
            $table->text('layer3_code')->nullable();
            $table->json('layer3_execution_result')->nullable();
            $table->json('layer4_planning_snapshot')->nullable();

            $table->enum('cac_complexity_at_sub', ['low', 'mid', 'high']);
            $table->enum('cac_autonomy_at_sub', ['low', 'mid', 'high']);
            $table->enum('cac_context_at_sub', ['low', 'mid', 'high']);
            $table->string('rank_at_submission', 20);

            $table->timestamp('submitted_at')->useCurrent();

            $table->foreign('learner_session_id')
                ->references('id')->on('learner_sessions')->cascadeOnDelete();
            $table->foreign('learner_id')
                ->references('id')->on('learners')->cascadeOnDelete();
            $table->foreign('task_id')
                ->references('id')->on('tasks')->restrictOnDelete();
            $table->foreign('scenario_id')
                ->references('id')->on('scenario_templates')->restrictOnDelete();
            $table->foreign('sprint_id')
                ->references('id')->on('learner_sprints')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_packages');
    }
};
