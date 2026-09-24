<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: alter_learner_profiles_add_success_streak
 *
 * Counterpart to the existing failure_streak column — tracks consecutive
 * passing (non-diagnostic) evaluations, driving CAC Autonomy/Context
 * Fidelity escalation (BLD §7.3). Resets to 0 on any failure.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('learner_profiles', function (Blueprint $table) {
            $table->unsignedInteger('success_streak')->default(0)->after('failure_streak');
        });
    }

    public function down(): void
    {
        Schema::table('learner_profiles', function (Blueprint $table) {
            $table->dropColumn('success_streak');
        });
    }
};
