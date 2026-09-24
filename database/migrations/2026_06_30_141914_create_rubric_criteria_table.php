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
        Schema::create('rubric_criteria', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('rubric_set_id')->notNull();
            $table->foreign('rubric_set_id')
                ->references('id')
                ->on('rubric_sets')
                ->cascadeOnDelete();

            $table->uuid('task_id')->notNull();
            $table->foreign('task_id')
                ->references('id')
                ->on('tasks')
                ->cascadeOnDelete();

            // A human-readable label for this criterion within the task
            // e.g. "Database schema design", "Error handling completeness"
            $table->string('task_dimension_label', 200)->notNull();

            // Which of the six competence dimensions this criterion measures
            $table->string('parent_dimension_id', 20)->notNull();
            $table->foreign('parent_dimension_id')
                ->references('id')
                ->on('competence_dimensions')
                ->restrictOnDelete();

            // Which complexity level this criterion applies to
            $table->enum('complexity_level', ['low', 'mid', 'high'])->notNull();

            // The criterion statement: "The learner correctly identified all entities..."
            $table->text('criterion_text')->notNull();

            // Weight of this criterion within its dimension for this task (0–1)
            $table->decimal('weight', 4, 3)->notNull();

            // Weight of the parent dimension across the whole task (0–1)
            // All dimension_weight values for the same task must sum to 1.0
            $table->decimal('dimension_weight', 4, 3)->notNull();

            // Instructions for Claude: "Look for evidence that the learner..."
            // This is the most important field — write it carefully.
            $table->text('claude_detection_hint')->notNull();

            // Prose description of what a Distinguished (4/4) response looks like
            $table->text('distinguished_description')->notNull();

            // Prose description of what a Proficient (3/4) response looks like
            $table->text('proficient_description')->notNull();

            // Prose description of what a Developing (2/4) response looks like
            $table->text('developing_description')->notNull();

            // Prose description of what a Beginning (1/4) response looks like
            $table->text('beginning_description')->notNull();

            // true = this criterion is specifically about architectural decisions
            $table->boolean('is_architectural')->default(false);

            // true = this criterion evaluates the planning layer deliverable
            $table->boolean('is_planning_layer')->default(false);

            // Optional anchor into a reference document — e.g. "See SRS section 3.2"
            $table->text('reference_doc_anchor')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubric_criteria');
    }

};
