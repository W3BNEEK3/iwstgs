<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_human_review_queue_table
 *
 * A real lifecycle row (pending -> in_review -> resolved) — unlike most
 * tables in this system, this one IS meant to be reloaded and mutated by a
 * human reviewer (a content_author/org_admin user), which is why it gets a
 * domain aggregate in AIMediation rather than the byproduct treatment.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('human_review_queue', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('submission_id');
            $table->uuid('evaluation_id');
            $table->uuid('learner_id');

            $table->enum('status', ['pending', 'in_review', 'resolved'])->default('pending');
            $table->uuid('reviewer_id')->nullable();
            $table->enum('reviewer_decision', ['proficient', 'not_proficient', 'escalate'])->nullable();
            $table->text('reviewer_notes')->nullable();

            $table->timestamp('queued_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();

            $table->foreign('submission_id')
                ->references('id')->on('submission_packages')->cascadeOnDelete();
            $table->foreign('evaluation_id')
                ->references('id')->on('evaluation_results')->cascadeOnDelete();
            $table->foreign('learner_id')
                ->references('id')->on('learners')->cascadeOnDelete();
            $table->foreign('reviewer_id')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('human_review_queue');
    }
};
