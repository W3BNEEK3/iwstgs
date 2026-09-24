<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_learner_profiles_table
 *
 * Owns the learner's live rank and CAC settings.
 * FK dependency: learners (must exist first).
 *
 * Reconciliation notes (Phase 5a §3):
 * #1 — current_rank_tier is varchar(20), NOT a DB enum (Data Model wins over the Plan's enum).
 * #5 — Only updated_at (no created_at); profile is mutated in place, not event-sourced here.
 * #6 — All rank levels / counters are smallint unsigned (Data Model wins over Plan's tinyint mix).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('learner_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_id');

            // #1 — varchar(20), not enum
            $table->string('current_rank_tier', 20)->default('Junior');
            // #6 — smallint unsigned
            $table->smallInteger('current_rank_level')->unsigned()->default(1);

            // CAC (Complexity-Autonomy-Context) live settings, adjusted by the EvalEngine
            $table->enum('cac_complexity',       ['low', 'mid', 'high'])->default('low');
            $table->enum('cac_autonomy',         ['low', 'mid', 'high'])->default('mid');
            $table->enum('cac_context_fidelity', ['low', 'mid', 'high'])->default('low');

            // Set to true when the EvalEngine detects a persistent rank/performance mismatch
            $table->boolean('mismatch_flag_active')->default(false);

            // #6 — smallint unsigned
            $table->smallInteger('failure_streak')->unsigned()->default(0);

            // #5 — no created_at; this row is mutated, not appended
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // One learner → one profile (1:1)
            $table->unique('learner_id');
            $table->foreign('learner_id')
                ->references('id')
                ->on('learners')
                ->cascadeOnDelete(); // profile has no meaning without the learner
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learner_profiles');
    }
};
