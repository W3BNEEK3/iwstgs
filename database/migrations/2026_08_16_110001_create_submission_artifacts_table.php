<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_submission_artifacts_table
 *
 * Uploaded-file metadata for a submission's Layer 2 (artifact) deliverables.
 * cascadeOnDelete on submission_id — artifacts have no life outside the
 * submission package they belong to, unlike the restrictOnDelete content
 * references on submission_packages itself.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('submission_artifacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('submission_id');

            $table->string('filename', 300);
            $table->enum('artifact_type', ['diagram', 'document', 'notes', 'other']);
            $table->text('storage_path');

            $table->timestamp('uploaded_at')->useCurrent();

            $table->foreign('submission_id')
                ->references('id')->on('submission_packages')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_artifacts');
    }
};
