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
        Schema::create('concept_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // The concept name itself. Must be unique across the whole tag vocabulary.
            // Use kebab-case: 'dependency-injection', 'sql-joins', 'rest-api-design'
            $table->string('tag', 200)->unique()->notNull();

            // Which area this concept belongs to
            $table->enum('category', [
                'se_concept',            // Software engineering: SOLID, DI, testing patterns
                'cs_fundamental',        // Computer science: algorithms, data structures, complexity
                'tool_technology',       // Tools: Git, Docker, Laravel, MySQL
                'professional_practice', // Process: PR reviews, standups, estimating
                'regulatory',            // Compliance: GDPR, HIPAA, accessibility standards
            ])->notNull();

            $table->text('description')->nullable();

            // How this tag was added to the system
            $table->enum('source', [
                'manual',
                'ai_recommended_accepted',
            ])->default('manual')->notNull();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concept_tags');
    }

};
