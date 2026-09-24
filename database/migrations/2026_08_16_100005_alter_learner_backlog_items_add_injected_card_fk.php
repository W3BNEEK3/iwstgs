<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: alter_learner_backlog_items_add_injected_card_fk
 *
 * Closes the deferred FK noted in create_learner_backlog_items_table: adds
 * the FK on injected_card_id now that injected_task_cards exists.
 * nullOnDelete — a backlog item survives its originating card being removed.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('learner_backlog_items', function (Blueprint $table) {
            $table->foreign('injected_card_id')
                ->references('id')
                ->on('injected_task_cards')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('learner_backlog_items', function (Blueprint $table) {
            $table->dropForeign(['injected_card_id']);
        });
    }
};
