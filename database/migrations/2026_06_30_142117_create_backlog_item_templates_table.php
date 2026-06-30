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
        Schema::create('backlog_item_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('project_id')->notNull();
            $table->foreign('project_id')
                ->references('id')
                ->on('project_templates')
                ->cascadeOnDelete();

            $table->string('title', 300)->notNull();
            $table->text('description')->notNull();

            // MoSCoW priority
            $table->enum('default_priority', [
                'must_have',
                'should_have',
                'could_have',
                'wont_have',
            ])->notNull();

            // JSON: roles that would typically pick up this backlog item
            $table->json('role_tags')->notNull();

            // The task that gets unlocked when this backlog item is in-sprint.
            // Nullable: not all backlog items map directly to a task.
            $table->uuid('task_id')->nullable();
            $table->foreign('task_id')
                ->references('id')
                ->on('tasks')
                ->nullOnDelete();

            // JSON: array of backlog_item_template IDs that must be 'done' first
            $table->json('dependency_item_ids')->nullable();

            // Controls the order items appear in the backlog sidebar
            $table->smallInteger('display_order')->unsigned()->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backlog_item_templates');
    }

};
