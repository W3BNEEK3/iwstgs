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
        Schema::create('project_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // "MedQueue Pharmacy Management System"
            $table->string('title', 300)->notNull();

            // One-line summary displayed in the project catalogue
            $table->string('tagline', 500)->nullable();

            // The domain/industry of the simulated company
            // e.g. "healthcare", "fintech", "logistics", "saas"
            $table->string('project_type', 100)->notNull();

            $table->string('business_domain', 200)->nullable();

            // The full business context document shown to learners during induction.
            // Multi-paragraph rich text.
            $table->text('business_context')->notNull();

            // JSON: array of {name, role, goals} objects
            // Represents the fictional stakeholders in the project
            $table->json('stakeholders')->nullable();

            // JSON: array of strings — constraints that apply to the whole project
            // e.g. ["Must support 500 concurrent users", "HIPAA compliant"]
            $table->json('overarching_constraints')->nullable();

            // JSON: {language, framework, database, deployment, ...}
            // The technical environment the learner is working in
            $table->json('tech_context')->nullable();

            // JSON: array of strings — role/skill tags to match against role_definitions
            // e.g. ["php", "laravel", "backend", "api"]
            $table->json('specialization_tags')->notNull();

            // FK to organisations — allows org-specific projects (Phase 5+)
            $table->uuid('organisation_id')->nullable();
            $table->foreign('organisation_id')
                ->references('id')
                ->on('organisations')
                ->nullOnDelete();

            // Intentionally no FK here yet — rubric_sets does not exist yet.
            // Step 2.15 adds the FK constraint after rubric_sets is created.
            $table->uuid('rubric_set_id')->nullable();

            // Optional coding guidelines shown in the Artifact Vault
            $table->text('coding_guidelines')->nullable();

            // JSON: {sprints: 3, tasks_per_sprint: 4, avg_task_points: 3}
            // Used during sprint planning to suggest how many items to pull in
            $table->json('velocity_estimate')->nullable();

            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])
                ->default('intermediate')
                ->notNull();

            // is_published: a content author has approved this project for learners
            $table->boolean('is_published')->default(false);

            // is_active: the project has not been archived
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_templates');
    }

};
