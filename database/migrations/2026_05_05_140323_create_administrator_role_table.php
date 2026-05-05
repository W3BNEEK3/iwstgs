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
        Schema::create('administrator_role', function (Blueprint $table) {
            $table->uuid('administrator_id');
            $table->unsignedBigInteger('role_id');
            $table->foreign('administrator_id')
                ->references('id')
                ->on('administrator')
                ->cascadeOnDelete();
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->cascadeOnDelete();
            $table->primary(['administrator_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('administrator_role');
    }
};
