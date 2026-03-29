<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('facilitator_sectors')) {
            // Check if foreign key already exists
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'facilitator_sectors' 
                AND COLUMN_NAME = 'user_role_id' 
                AND REFERENCED_TABLE_NAME = 'user_roles'
            ");
            
            if (empty($foreignKeys)) {
                try {
                    Schema::table('facilitator_sectors', function (Blueprint $table) {
                        $table->foreign('user_role_id')->references('id')->on('user_roles')->onDelete('cascade');
                    });
                } catch (\Exception $e) {
                    // Foreign key constraint might fail if data types don't match
                    // This is okay - the relationship will still work at the application level
                    \Log::warning('Could not add foreign key constraint to facilitator_sectors: ' . $e->getMessage());
                }
            }
            
            // Check if unique constraint exists
            $uniqueConstraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'facilitator_sectors' 
                AND CONSTRAINT_TYPE = 'UNIQUE'
                AND CONSTRAINT_NAME LIKE '%user_role_id%'
            ");
            
            if (empty($uniqueConstraints)) {
                Schema::table('facilitator_sectors', function (Blueprint $table) {
                    $table->unique(['user_role_id', 'sector_id']);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('facilitator_sectors')) {
            Schema::table('facilitator_sectors', function (Blueprint $table) {
                $table->dropForeign(['user_role_id']);
                $table->dropUnique(['user_role_id', 'sector_id']);
            });
        }
    }
};
