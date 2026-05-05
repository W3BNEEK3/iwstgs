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
        Schema::create('learner_role', function (Blueprint $table) {
            $table->uuid('learner_id');
            $table->unsignedBigInteger('role_id');
            $table->foreign('learner_id')
                ->references('id')
                ->on('learners')
                ->cascadeOnDelete();
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->cascadeOnDelete();
            $table->primary(['learner_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learner_role');
    }
};
