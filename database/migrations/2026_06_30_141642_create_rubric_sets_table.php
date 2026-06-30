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
        Schema::create('rubric_sets', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // UNIQUE enforces one-to-one: each project has exactly one rubric set
            $table->uuid('project_id')->unique()->notNull();
            $table->foreign('project_id')
                ->references('id')
                ->on('project_templates')
                ->cascadeOnDelete();

            // Version string — useful for tracking rubric revisions
            // e.g. "1.0", "1.1", "2.0"
            $table->string('version', 20)->default('1.0')->notNull();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubric_sets');
    }

};
