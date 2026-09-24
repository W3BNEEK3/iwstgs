<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gap 3 — Adds final_competency_snapshot to learner_profiles. This JSON blob
 * is written by GenerateFinalCompetencyGraphHandler when all project scenarios
 * are complete. It stores a point-in-time snapshot of dimension scores,
 * concept mastery, rank, and CAC trajectory, so the Reporting dashboard can
 * always show the state at session-end even if scoring logic changes later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learner_profiles', function (Blueprint $table) {
            $table->json('final_competency_snapshot')
                ->nullable()
                ->after('success_streak')
                ->comment('Set once by GenerateFinalCompetencyGraphHandler when the project session completes');
        });
    }

    public function down(): void
    {
        Schema::table('learner_profiles', function (Blueprint $table) {
            $table->dropColumn('final_competency_snapshot');
        });
    }
};
