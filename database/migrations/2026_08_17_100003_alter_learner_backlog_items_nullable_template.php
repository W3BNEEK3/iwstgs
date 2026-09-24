<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: alter_learner_backlog_items_nullable_template
 *
 * template_item_id was NOT NULL from Phase 6, when every backlog item was
 * assumed to be seeded from an admin-authored backlog_item_template row.
 * Phase 9's injected consequence/suggestion tasks have no such row — they're
 * selected dynamically from a task's consequence_task_ids/suggestion_task_ids,
 * not authored as backlog content — so an injected item has template_item_id
 * NULL and injected_card_id set instead (the inverse of a template-seeded item).
 *
 * Uses the schema builder (not raw MySQL) so it also runs on SQLite.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('learner_backlog_items', function (Blueprint $table) {
            $table->dropForeign(['template_item_id']);
        });
        Schema::table('learner_backlog_items', function (Blueprint $table) {
            $table->uuid('template_item_id')->nullable()->change();
            $table->foreign('template_item_id')->references('id')->on('backlog_item_templates');
        });
    }

    public function down(): void
    {
        Schema::table('learner_backlog_items', function (Blueprint $table) {
            $table->dropForeign(['template_item_id']);
        });
        Schema::table('learner_backlog_items', function (Blueprint $table) {
            $table->uuid('template_item_id')->nullable(false)->change();
            $table->foreign('template_item_id')->references('id')->on('backlog_item_templates');
        });
    }
};
