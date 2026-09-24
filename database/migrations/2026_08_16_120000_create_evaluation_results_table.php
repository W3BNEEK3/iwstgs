<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_evaluation_results_table
 *
 * One row per submission_package — cascadeOnDelete + UNIQUE on submission_id
 * (a real 1:1, unlike submission_packages' own 1:many attempt history).
 * No domain aggregate: written once by EvaluationService after Claude
 * responds, never mutated afterward (same byproduct reasoning as
 * submission_packages itself).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('evaluation_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('submission_id')->unique();
            $table->uuid('learner_id');

            $table->enum('overall_tier', ['beginning', 'developing', 'proficient', 'distinguished']);
            $table->boolean('passes_threshold');
            $table->enum('gap_type', ['knowledge_gap', 'strategy_gap'])->nullable();
            $table->boolean('is_uncertain')->default(false);
            $table->uuid('follow_up_prompt_id')->nullable();

            $table->timestamp('evaluated_at')->useCurrent();

            $table->foreign('submission_id')
                ->references('id')->on('submission_packages')->cascadeOnDelete();
            $table->foreign('learner_id')
                ->references('id')->on('learners')->cascadeOnDelete();
            $table->foreign('follow_up_prompt_id')
                ->references('id')->on('follow_up_prompt_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_results');
    }
};
