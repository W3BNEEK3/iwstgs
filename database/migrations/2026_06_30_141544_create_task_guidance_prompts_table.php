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
        Schema::create('task_guidance_prompts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('task_id')->notNull();
            $table->foreign('task_id')
                ->references('id')
                ->on('tasks')
                ->cascadeOnDelete();

            // Which dimension this prompt is aimed at helping with
            // e.g. "dim_004" for Testing — or a descriptive label like "test-coverage"
            $table->string('trigger_dimension', 200)->notNull();

            // The actual guidance text shown to the learner
            $table->text('prompt_text')->notNull();

            // Which autonomy level should see this prompt
            // low = shown to learners with lots of scaffolding (beginners)
            // high = shown even to autonomous learners (expert-level hints)
            $table->enum('autonomy_level_filter', ['low', 'mid', 'high'])->notNull();

            // proactive = shown without the learner asking
            // reactive = shown only when the learner requests help
            $table->enum('delivery_mode', ['proactive', 'reactive'])->notNull();

            $table->tinyInteger('display_order')->unsigned()->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_guidance_prompts');
    }

};
