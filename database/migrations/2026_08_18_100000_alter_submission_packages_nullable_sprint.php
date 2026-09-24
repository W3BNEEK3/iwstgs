<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: alter_submission_packages_nullable_sprint
 *
 * sprint_id was NOT NULL because every submission was assumed to belong to
 * an active sprint. The diagnostic pathway (Implementation Plan §5.5) breaks
 * that assumption — Integration Spec §10: "For diagnostic scenarios, the
 * Planning Layer is inactive. Sprint board state is not evaluated during
 * diagnostic assessment." A diagnostic submission has no sprint at all.
 *
 * Uses the schema builder (not raw MySQL) so it also runs on SQLite.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('submission_packages', function (Blueprint $table) {
            $table->dropForeign(['sprint_id']);
        });
        Schema::table('submission_packages', function (Blueprint $table) {
            $table->uuid('sprint_id')->nullable()->change();
            $table->foreign('sprint_id')->references('id')->on('learner_sprints')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('submission_packages', function (Blueprint $table) {
            $table->dropForeign(['sprint_id']);
        });
        Schema::table('submission_packages', function (Blueprint $table) {
            $table->uuid('sprint_id')->nullable(false)->change();
            $table->foreign('sprint_id')->references('id')->on('learner_sprints')->restrictOnDelete();
        });
    }
};
