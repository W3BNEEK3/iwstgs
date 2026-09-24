<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration: alter_submission_packages_nullable_sprint
 *
 * sprint_id was NOT NULL because every submission was assumed to belong to
 * an active sprint. The diagnostic pathway (Implementation Plan §5.5) breaks
 * that assumption — Integration Spec §10: "For diagnostic scenarios, the
 * Planning Layer is inactive. Sprint board state is not evaluated during
 * diagnostic assessment." A diagnostic submission has no sprint at all.
 *
 * Raw SQL throughout — doctrine/dbal (needed by Blueprint::change()) isn't
 * installed in this project. Same pattern as
 * alter_learner_backlog_items_nullable_template.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE submission_packages DROP FOREIGN KEY submission_packages_sprint_id_foreign');
        DB::statement('ALTER TABLE submission_packages MODIFY sprint_id CHAR(36) NULL');
        DB::statement('ALTER TABLE submission_packages ADD CONSTRAINT submission_packages_sprint_id_foreign FOREIGN KEY (sprint_id) REFERENCES learner_sprints(id) ON DELETE RESTRICT');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE submission_packages DROP FOREIGN KEY submission_packages_sprint_id_foreign');
        DB::statement('ALTER TABLE submission_packages MODIFY sprint_id CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE submission_packages ADD CONSTRAINT submission_packages_sprint_id_foreign FOREIGN KEY (sprint_id) REFERENCES learner_sprints(id) ON DELETE RESTRICT');
    }
};
