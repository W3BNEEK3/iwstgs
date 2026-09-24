<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: alter_habit_flags_add_source_task
 *
 * The Implementation Plan's habit_flags schema has no way to know which
 * task/dimension a habit relates to — just a free-text habit_description —
 * yet §9.3 says SuggestionTaskInjector must later pick a real task from
 * *some* task's suggestion_task_ids for this exact habit. source_task_id
 * tracks the most recent task whose evaluation reinforced this habit,
 * updated on each repeat observation, giving the injector something
 * concrete to read suggestion_task_ids from.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('habit_flags', function (Blueprint $table) {
            $table->uuid('source_task_id')->nullable()->after('habit_description');
            $table->foreign('source_task_id')
                ->references('id')->on('tasks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('habit_flags', function (Blueprint $table) {
            $table->dropForeign(['source_task_id']);
            $table->dropColumn('source_task_id');
        });
    }
};
