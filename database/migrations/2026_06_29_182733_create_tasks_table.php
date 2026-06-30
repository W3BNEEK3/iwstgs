<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('scenario_id')->notNull();
            $table->foreign('scenario_id')
                ->references('id')
                ->on('scenario_templates')
                ->cascadeOnDelete();

            // Position within the scenario
            $table->smallInteger('sequence_order')->unsigned()->notNull();

            $table->string('title', 300)->notNull();

            // The main task description shown to the learner.
            // For CAC-enabled tasks, this is the base brief; variants are in task_cac_variants.
            $table->text('task_brief')->notNull();

            // Which technical domain this task belongs to
            // e.g. "backend", "database", "api-design", "testing"
            $table->string('domain', 100)->notNull();

            $table->enum('task_type', [
                'core',
                'consequence',
                'suggestion',
                'diagnostic_scenario',
                'diagnostic_consequence',
            ])->default('core')->notNull();

            // JSON: array of role tags — which roles would typically do this task
            // e.g. ["backend-developer", "full-stack-developer"]
            $table->json('role_tags')->notNull();

            // JSON: array of tool names the learner should use
            // e.g. ["git", "phpunit", "postman"]
            $table->json('tools')->nullable();

            // JSON: array of concept tag IDs — concepts the learner is expected to know
            $table->json('prerequisite_concepts')->nullable();

            // true = CAC levels are assigned at runtime based on learner's rank
            // false = use the fixed_* columns below
            $table->boolean('is_cac_runtime_set')->default(true);

            // Used when is_cac_runtime_set = false
            $table->enum('fixed_complexity', ['low', 'mid', 'high'])->nullable();
            $table->enum('fixed_autonomy', ['low', 'mid', 'high'])->nullable();
            $table->enum('fixed_context_fidelity', ['low', 'mid', 'high'])->nullable();

            // JSON: array of task IDs to inject as consequences if this task is failed
            $table->json('consequence_task_ids')->nullable();

            // JSON: array of task IDs to suggest as optional growth after this task
            $table->json('suggestion_task_ids')->nullable();

            // true = the learner must produce an architectural design artifact
            $table->boolean('is_architectural')->default(false);

            // true = the sprint planning layer is active for this task
            // (learner must plan their approach before implementing)
            $table->boolean('planning_layer_active')->default(false);

            // JSON: Docker execution configuration for code deliverable tasks
            // Phase 7 feature — null until code execution is built
            $table->json('code_execution_config')->nullable();

            // A short summary of what a model response looks like (for author reference)
            $table->text('model_response_summary')->nullable();

            // Optional time limit for the task — null means no limit
            $table->integer('time_limit_minutes')->unsigned()->nullable();

            $table->boolean('is_published')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['scenario_id', 'sequence_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }

};
