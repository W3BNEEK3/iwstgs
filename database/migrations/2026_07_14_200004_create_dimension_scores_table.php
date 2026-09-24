<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_dimension_scores_table
 *
 * Stores a learner's current performance tier per competence dimension.
 * One row per (learner, dimension) pair — the composite unique enforces this.
 * FK dependencies: learners, competence_dimensions.
 *
 * Reconciliation #3: dimension_id is varchar(20) NOT text.
 * Reason: competence_dimensions.id is varchar(20); a MySQL FK column must exactly match
 * the referenced column's type. The Data Model's "TEXT" would prevent FK creation.
 * varchar(20) matches the actual PK type and is the correct resolution.
 *
 * Delete behaviours:
 * - cascade on learner_id: scores die with the learner.
 * - restrict on dimension_id: you should not delete a competence dimension that learners
 *   have scored against — would silently invalidate all evaluation history.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('dimension_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_id');

            // #3 — varchar(20) to match competence_dimensions.id FK type
            $table->string('dimension_id', 20);

            $table->enum('tier', ['untested', 'basic', 'intermediate', 'advanced'])
                ->default('untested');

            // #6 — smallint unsigned
            $table->smallInteger('evidence_count')->unsigned()->default(0);

            $table->timestamp('last_updated_at')->nullable();

            // One score row per learner per dimension
            $table->unique(['learner_id', 'dimension_id']);

            $table->foreign('learner_id')
                ->references('id')
                ->on('learners')
                ->cascadeOnDelete();

            // #3 — FK to varchar(20) PK works correctly
            $table->foreign('dimension_id')
                ->references('id')
                ->on('competence_dimensions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dimension_scores');
    }
};
