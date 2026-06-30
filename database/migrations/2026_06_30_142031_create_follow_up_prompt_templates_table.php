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
        Schema::create('follow_up_prompt_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Which technical domain this prompt applies to
            // e.g. "database-design", "testing", "api-design"
            $table->string('domain', 100)->notNull();

            // The follow-up question text
            // e.g. "You mentioned using a foreign key here — why did you choose that
            // over a soft reference?"
            $table->text('prompt_text')->notNull();

            // What condition triggers this follow-up to be selected
            // e.g. "Learner mentioned indexing but did not explain rationale"
            $table->text('trigger_condition')->notNull();

            // true = this question is specifically designed to detect copied answers
            // (questions only a person who actually did the work could answer)
            $table->boolean('is_anti_copy')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_up_prompt_templates');
    }

};
