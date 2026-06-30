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
        Schema::table('project_templates', function (Blueprint $table) {
            // Add the FK constraint on the existing rubric_set_id column.
            // The column already exists as nullable UUID — we are only adding the constraint.
            $table->foreign('rubric_set_id')
                ->references('id')
                ->on('rubric_sets')
                ->nullOnDelete();
            // nullOnDelete: if a rubric_set is deleted, set project rubric_set_id to NULL
            // rather than deleting the project. Projects outlive their rubric sets.
        });
    }

    public function down(): void
    {
        Schema::table('project_templates', function (Blueprint $table) {
            // Must drop the FK constraint before rolling back, or MySQL will complain.
            $table->dropForeign(['rubric_set_id']);
        });
    }

};
