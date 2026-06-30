<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Display name: "Junior PHP Developer", "Mid-Level Backend Engineer"
            $table->string('title', 200)->notNull();

            // JSON array of strings: ["php", "laravel", "backend"]
            // Used to match a role to a project's specialization_tags.
            $table->json('specialization_tags')->notNull();

            // Minimum years of experience to enrol in a session targeting this role.
            // 0 means anyone can enrol regardless of experience.
            $table->tinyInteger('min_years_experience')->unsigned()->default(0);

            // JSON object: {"dim_001": 0.20, "dim_002": 0.25, ...}
            // Weights must sum to 1.0. The EvalEngine uses these to compute weighted scores.
            $table->json('dimension_weights')->notNull();

            // JSON object: {"dim_001": "basic", "dim_002": "intermediate", ...}
            // The minimum tier a learner must reach in each dimension to qualify for this role.
            // Valid tier values: "basic", "intermediate", "advanced"
            $table->json('dimension_thresholds')->notNull();

            // true = this is a lead role (e.g. Tech Lead, Senior Architect).
            // Lead roles may have additional qualifications in Phase 10.
            $table->boolean('is_lead_role')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_definitions');
    }

};
