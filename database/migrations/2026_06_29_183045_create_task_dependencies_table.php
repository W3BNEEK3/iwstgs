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
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // The task that has a prerequisite
            $table->uuid('task_id')->notNull();
            $table->foreign('task_id')
                ->references('id')
                ->on('tasks')
                ->cascadeOnDelete();

            // The task that must be completed first
            $table->uuid('prerequisite_task_id')->notNull();
            $table->foreign('prerequisite_task_id')
                ->references('id')
                ->on('tasks')
                ->restrictOnDelete();
            // RESTRICT: if someone tries to delete a task that is a prerequisite
            // of another task, the database will refuse — forces explicit cleanup.

            // No two rows with the same (task_id, prerequisite_task_id) pair
            $table->unique(['task_id', 'prerequisite_task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }

};
