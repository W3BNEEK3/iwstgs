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
        Schema::create('artifact_vault_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('project_id')->notNull();
            $table->foreign('project_id')
                ->references('id')
                ->on('project_templates')
                ->cascadeOnDelete();

            // The type of document — controls the icon shown in the vault sidebar
            $table->enum('document_type', [
                'business_context',
                'prd',
                'srs',
                'sad',               // Software Architecture Document
                'coding_guidelines',
                'glossary',
                'sprint_goal_template',
            ])->notNull();

            $table->string('title', 300)->notNull();
            $table->text('content')->notNull();

            // Optional: only available to learners above this rank
            // e.g. "Mid-2" — format is "{tier}-{level}"
            $table->string('rank_gate', 20)->nullable();

            // Optional: only available at or after this session phase
            $table->enum('phase_gate', [
                'pre_induction',
                'post_induction',
                'post_sprint_1',
                'mid_session',
                'advanced_only',
            ])->nullable();

            // true = shown alongside tasks as a reference, not just in the vault
            $table->boolean('is_reference_doc')->default(false);

            $table->smallInteger('display_order')->unsigned()->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artifact_vault_items');
    }

};
