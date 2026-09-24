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
        Schema::create('scenario_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('project_id')->notNull();
            $table->foreign('project_id')
                ->references('id')
                ->on('project_templates')
                ->cascadeOnDelete();
            // When a project is deleted, all its scenarios are deleted too.
            // This makes sense: scenarios have no meaning without their project.

            // Position of this scenario within the project (1, 2, 3, ...)
            $table->smallInteger('sequence_order')->unsigned()->notNull();

            $table->string('title', 300)->notNull();

            // Multi-paragraph text setting the scene before the situation trigger.
            // Read-aloud style: "You've just joined the MedQueue team as a junior
            // developer. It's your second week..."
            $table->text('narrative_context')->notNull();

            // The specific event that triggers the learner's task.
            // The actual content of the email, Slack message, etc.
            $table->text('situation_trigger')->notNull();

            // Controls how the UI renders the trigger
            $table->enum('situation_trigger_type', [
                'slack_message',
                'email',
                'meeting_summary',
                'incident_report',
                'ticket',
                'handover_note',
            ])->notNull();

            // The fictional title the learner holds in this scenario
            // e.g. "Junior Backend Developer", "Sole Developer"
            $table->string('learner_role_label', 200)->nullable();

            // The default autonomy level — how much the learner is left to figure
            // out on their own. Can be overridden per-task via CAC variants.
            $table->enum('default_autonomy_level', ['low', 'mid', 'high'])
                ->default('mid')
                ->notNull();

            // true = this is the diagnostic scenario used for initial rank assignment
            $table->boolean('is_diagnostic')->default(false);

            $table->boolean('is_published')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Ensures no two scenarios in the same project share the same position.
            // This is a COMPOSITE unique constraint: both columns together must be unique.
            $table->unique(['project_id', 'sequence_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenario_templates');
    }

};
