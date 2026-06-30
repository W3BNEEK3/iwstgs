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
        Schema::create('task_knowledge_anchors', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('task_id')->notNull();
            $table->foreign('task_id')
                ->references('id')
                ->on('tasks')
                ->cascadeOnDelete();

            $table->uuid('concept_id')->notNull();
            $table->foreign('concept_id')
                ->references('id')
                ->on('concept_tags')
                ->restrictOnDelete();
            // RESTRICT: do not allow a concept to be deleted while tasks depend on it.

            // Denormalised copy of the concept name — avoids a JOIN when displaying
            // knowledge anchors in the admin UI without loading the concept_tags table.
            $table->string('concept_name', 200)->notNull();

            // Which technical area this anchor belongs to
            // e.g. "object-oriented-design", "database-normalisation"
            $table->string('domain', 200)->notNull();

            // What specifically should the learner demonstrate?
            // e.g. "Show understanding of dependency injection by using constructor injection
            // rather than service locator pattern"
            $table->text('application_expectation')->notNull();

            // Is demonstrating this concept required to pass the task?
            $table->boolean('is_required')->default(false);

            // What to tell the learner if they missed this concept
            $table->text('remediation_hint')->notNull();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_knowledge_anchors');
    }

};
