<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_concept_tag_recommendations_table
 *
 * Claude's suggestions for content improvements surfaced during evaluation
 * (a new concept tag it noticed, a performance-level description that
 * seems off) — reviewed and accepted/rejected by a curator. Real lifecycle
 * row (pending -> accepted/rejected), same reasoning as human_review_queue.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('concept_tag_recommendations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('source_submission_id');
            $table->uuid('task_id');

            $table->enum('recommendation_type', ['new_concept_tag', 'update_performance_levels', 'update_criteria']);
            $table->json('proposed_content');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->uuid('curator_id')->nullable();
            $table->text('curator_notes')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();

            $table->foreign('source_submission_id')
                ->references('id')->on('submission_packages')->cascadeOnDelete();
            $table->foreign('task_id')
                ->references('id')->on('tasks')->restrictOnDelete();
            $table->foreign('curator_id')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concept_tag_recommendations');
    }
};
