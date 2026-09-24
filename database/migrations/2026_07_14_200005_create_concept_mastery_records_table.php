<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_concept_mastery_records_table
 *
 * Tracks a learner's mastery of individual concept tags across all tasks.
 * One row per (learner, concept) pair — the composite unique enforces this.
 * FK dependencies: learners, concept_tags.
 *
 * Delete behaviours:
 * - cascade on learner_id: mastery records die with the learner.
 * - cascade on concept_id: if a concept tag is retired/deleted, remove its mastery rows
 *   (the concept no longer exists so the record has no meaning).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('concept_mastery_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_id');
            $table->uuid('concept_id');

            $table->enum('status', [
                'not_encountered',
                'encountered',
                'partially_met',
                'mastered',
            ])->default('not_encountered');

            // #6 — smallint unsigned
            $table->smallInteger('tasks_encountered')->unsigned()->default(0);
            $table->smallInteger('tasks_met')->unsigned()->default(0);

            $table->timestamp('last_updated_at')->nullable();

            // One mastery row per learner per concept
            $table->unique(['learner_id', 'concept_id']);

            $table->foreign('learner_id')
                ->references('id')
                ->on('learners')
                ->cascadeOnDelete();

            $table->foreign('concept_id')
                ->references('id')
                ->on('concept_tags')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concept_mastery_records');
    }
};
