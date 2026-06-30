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
        Schema::create('competence_dimensions', function (Blueprint $table) {
            // String PK: 'dim_001' through 'dim_006'
            // No auto-increment — these IDs are intentional and permanent.
            $table->string('id', 20)->primary();

            $table->string('name', 200)->notNull();

            // Short label used in UI badges, charts, and column headers
            $table->string('short_label', 100)->notNull();

            // The guiding question evaluators ask when scoring this dimension
            // e.g. "Did the learner correctly analyse and decompose the problem?"
            $table->text('core_question')->notNull();

            // JSON array of strings — observable indicators of this dimension
            // e.g. ["Identifies constraints", "Separates concerns correctly"]
            $table->json('observable_indicators')->notNull();

            // Controls the order dimensions appear in charts and reports
            $table->tinyInteger('sequence_order')->unsigned()->notNull();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competence_dimensions');
    }

};
