<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BLD §11.4 — When AIMediation detects a persistent dimension weakness across
 * a full scenario, the next scenario assigned should specifically target that
 * dimension. This column is written by ScenarioTransitionService and read by
 * GetSprintPlanningHandler / GetSessionOnboardingHandler to surface a
 * "This scenario focuses on [X]" notice to the learner.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learner_sessions', function (Blueprint $table) {
            $table->uuid('targeted_dimension_id')
                ->nullable()
                ->after('current_scenario_id')
                ->comment('FK to competence_dimensions — set when a dimension weakness was detected at last scenario transition');

            $table->foreign('targeted_dimension_id')
                ->references('id')
                ->on('competence_dimensions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('learner_sessions', function (Blueprint $table) {
            $table->dropForeign(['targeted_dimension_id']);
            $table->dropColumn('targeted_dimension_id');
        });
    }
};
