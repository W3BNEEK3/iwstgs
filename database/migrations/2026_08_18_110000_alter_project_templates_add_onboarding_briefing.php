<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: alter_project_templates_add_onboarding_briefing
 *
 * Tiroco's generated one-way narrative onboarding brief — generated once
 * (lazily, on first learner request), cached here, and shown to every
 * learner who enrols in this project. One column, not a new table: v1 has
 * exactly one trigger point per project; a second trigger point is the
 * signal to promote this into a proper narrative-messages table.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('project_templates', function (Blueprint $table) {
            $table->text('onboarding_briefing')->nullable()->after('coding_guidelines');
        });
    }

    public function down(): void
    {
        Schema::table('project_templates', function (Blueprint $table) {
            $table->dropColumn('onboarding_briefing');
        });
    }
};
