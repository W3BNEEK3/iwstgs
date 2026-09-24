<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_injected_task_cards_table
 *
 * Consequence, suggestion, and diagnostic-consequence cards injected onto a
 * learner's sprint board. Nothing writes to this table yet — the injection
 * logic (failure-threshold detection, habit/pattern recognition) is Phase 9
 * (Adaptive Engine). This phase only needs the schema and a read path so the
 * sprint board can render the visual differentiation from Integration Spec
 * §13.4 once cards start appearing.
 *
 * source_task_id is restrictOnDelete (a content reference an injected card
 * exists because of); injected_task_id and target_sprint_id are nullable
 * soft pointers (nullOnDelete) — a generated/skipped card can outlive the
 * specific task or sprint it once pointed at.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('injected_task_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_session_id');
            $table->uuid('learner_id');

            $table->enum('card_type', ['consequence', 'suggestion', 'diagnostic_consequence']);
            $table->uuid('source_task_id');
            $table->uuid('injected_task_id')->nullable();
            $table->json('generated_task_content')->nullable();
            $table->uuid('target_sprint_id')->nullable();

            $table->enum('visual_treatment', ['consequence_amber', 'suggestion_teal', 'diagnostic_deep_blue']);
            $table->enum('status', ['pending', 'in_progress', 'submitted', 'skipped'])->default('pending');
            $table->enum('suggestion_type', ['corrective', 'extensional'])->nullable();

            $table->timestamp('injected_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();

            $table->foreign('learner_session_id')
                ->references('id')->on('learner_sessions')->cascadeOnDelete();
            $table->foreign('learner_id')
                ->references('id')->on('learners')->cascadeOnDelete();
            $table->foreign('source_task_id')
                ->references('id')->on('tasks')->restrictOnDelete();
            $table->foreign('injected_task_id')
                ->references('id')->on('tasks')->nullOnDelete();
            $table->foreign('target_sprint_id')
                ->references('id')->on('learner_sprints')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('injected_task_cards');
    }
};
