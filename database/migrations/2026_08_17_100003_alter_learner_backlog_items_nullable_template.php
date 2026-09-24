<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
 * Raw SQL throughout — doctrine/dbal (needed by Blueprint::change()) isn't
 * installed in this project.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE learner_backlog_items DROP FOREIGN KEY learner_backlog_items_template_item_id_foreign');
        DB::statement('ALTER TABLE learner_backlog_items MODIFY template_item_id CHAR(36) NULL');
        DB::statement('ALTER TABLE learner_backlog_items ADD CONSTRAINT learner_backlog_items_template_item_id_foreign FOREIGN KEY (template_item_id) REFERENCES backlog_item_templates(id)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE learner_backlog_items DROP FOREIGN KEY learner_backlog_items_template_item_id_foreign');
        DB::statement('ALTER TABLE learner_backlog_items MODIFY template_item_id CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE learner_backlog_items ADD CONSTRAINT learner_backlog_items_template_item_id_foreign FOREIGN KEY (template_item_id) REFERENCES backlog_item_templates(id)');
    }
};
