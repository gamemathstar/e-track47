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
        if (!Schema::hasTable('facilitator_sectors')) {
            Schema::create('facilitator_sectors', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_role_id');
                $table->foreignId('sector_id')->constrained('sectors')->onDelete('cascade');
                $table->timestamps();
                
                // Foreign key constraint
                $table->foreign('user_role_id')->references('id')->on('user_roles')->onDelete('cascade');
                
                // Ensure a facilitator can't have duplicate sector assignments
                $table->unique(['user_role_id', 'sector_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilitator_sectors');
    }
};
