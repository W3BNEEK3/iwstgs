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
        Schema::create('task_expected_deliverables', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('task_id')->notNull();
            $table->foreign('task_id')
                ->references('id')
                ->on('tasks')
                ->cascadeOnDelete();

            // What kind of deliverable this is — controls submission form rendering
            $table->enum('type', [
                'written_explanation', // Prose text answer
                'artifact',            // A named document (e.g. entity-relationship diagram)
                'code',                // Code snippet or file
                'diagram',             // Visual diagram (uploaded image)
                'document',            // Formatted document (e.g. PRD section)
            ])->notNull();

            // Short name shown as the deliverable label in the submission form
            // e.g. "System Design Diagram", "Test Plan"
            $table->string('label', 200)->notNull();

            // Explanation of what specifically to produce
            $table->text('description')->notNull();

            $table->boolean('is_required')->default(true);

            $table->tinyInteger('display_order')->unsigned()->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_expected_deliverables');
    }

};
