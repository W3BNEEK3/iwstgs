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
        Schema::create('task_cac_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('task_id')->notNull();
            $table->foreign('task_id')
                ->references('id')
                ->on('tasks')
                ->cascadeOnDelete();

            $table->enum('complexity_level', ['low', 'mid', 'high'])->notNull();

            // The version of the task brief at this complexity level.
            // Replaces or augments task_brief from the tasks table.
            $table->text('scenario_text')->notNull();

            // Scaffolding: step-by-step hints that appear at each autonomy level.
            // null = no scaffolding at that autonomy level.
            $table->text('scaffolding_text_low')->nullable();  // most guidance
            $table->text('scaffolding_text_mid')->nullable();  // moderate guidance
            $table->text('scaffolding_text_high')->nullable(); // no guidance

            // Context: additional environmental detail at each context-fidelity level.
            $table->text('context_text_low')->nullable();   // simplified context
            $table->text('context_text_mid')->nullable();   // moderate realism
            $table->text('context_text_high')->nullable();  // full production realism

            $table->unique(['task_id', 'complexity_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_cac_variants');
    }

};
