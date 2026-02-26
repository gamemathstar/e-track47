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
        // Update existing 'Sector Admin' records to 'Data Admin'
        DB::table('user_roles')
            ->where('role', 'Sector Admin')
            ->update(['role' => 'Data Admin']);

        // Modify the enum column to replace 'Sector Admin' with 'Data Admin'
        DB::statement("ALTER TABLE `user_roles` MODIFY COLUMN `role` ENUM('Governor','Sector Head','Data Admin','Coordinator','Deputy Coordinator','Facilitator','System Admin') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Update 'Data Admin' records back to 'Sector Admin'
        DB::table('user_roles')
            ->where('role', 'Data Admin')
            ->update(['role' => 'Sector Admin']);

        // Restore original enum
        DB::statement("ALTER TABLE `user_roles` MODIFY COLUMN `role` ENUM('Governor','Sector Head','Sector Admin','Coordinator','Deputy Coordinator','Facilitator','System Admin') NOT NULL");
    }
};
