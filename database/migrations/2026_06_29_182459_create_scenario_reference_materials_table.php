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
        Schema::create('scenario_reference_materials', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('scenario_id')->notNull();
            $table->foreign('scenario_id')
                ->references('id')
                ->on('scenario_templates')
                ->cascadeOnDelete();

            // What kind of document this is — controls the UI rendering style
            $table->enum('material_type', [
                'document',
                'email',
                'slack_message',
                'ticket',
                'report',
                'notes',
            ])->notNull();

            $table->string('title', 300)->notNull();

            // The full text content of the document
            $table->text('content')->notNull();

            // JSON: array of signal objects for the evaluation engine
            // e.g. [{"type": "terminology", "value": "idempotent"}]
            $table->json('embedded_signals')->nullable();

            // Controls the order materials appear in the sidebar
            $table->smallInteger('display_order')->unsigned()->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenario_reference_materials');
    }

};
