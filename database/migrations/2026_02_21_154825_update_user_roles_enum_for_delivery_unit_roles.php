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
        // Update existing 'Delivery Department' records to 'Coordinator' as default
        DB::table('user_roles')
            ->where('role', 'Delivery Department')
            ->update(['role' => 'Coordinator']);

        // Modify the enum column to include new roles
        // MySQL doesn't support direct enum modification, so we need to use ALTER TABLE
        DB::statement("ALTER TABLE `user_roles` MODIFY COLUMN `role` ENUM('Governor','Sector Head','Sector Admin','Coordinator','Deputy Coordinator','Facilitator','System Admin') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Update new roles back to 'Delivery Department'
        DB::table('user_roles')
            ->whereIn('role', ['Coordinator', 'Deputy Coordinator', 'Facilitator'])
            ->update(['role' => 'Delivery Department']);

        // Restore original enum
        DB::statement("ALTER TABLE `user_roles` MODIFY COLUMN `role` ENUM('Governor','Sector Head','Sector Admin','Delivery Department','System Admin') NOT NULL");
    }
};
