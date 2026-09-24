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
        Schema::create('learners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('fullname', 200);
            $table->enum('entry_category', ['inexperienced', 'experienced']);
            $table->smallInteger('years_experience')
                ->unsigned()
                ->nullable();
            $table->uuid('user_id');
            $table->uuid('organisation_id')
                ->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('organisation_id')
                ->references('id')
                ->on('organisations')
                ->nullOnDelete();
            $table->timestamp('last_active_at')
                ->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learners');
    }
};
