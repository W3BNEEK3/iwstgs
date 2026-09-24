<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_dimension_evaluations_table
 *
 * Per-dimension breakdown of an evaluation_results row — a true owned child
 * (cascadeOnDelete on evaluation_id), one to six rows per evaluation
 * (however many dimensions the task's rubric criteria actually touch).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('dimension_evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('evaluation_id');
            $table->string('dimension_id', 20);
            $table->string('task_dimension_label', 200);

            $table->enum('tier_achieved', ['beginning', 'developing', 'proficient', 'distinguished']);
            $table->json('criteria_met');
            $table->json('criteria_missed');
            $table->json('layer_scores');
            $table->text('evaluator_notes')->nullable();

            $table->foreign('evaluation_id')
                ->references('id')->on('evaluation_results')->cascadeOnDelete();
            $table->foreign('dimension_id')
                ->references('id')->on('competence_dimensions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dimension_evaluations');
    }
};
