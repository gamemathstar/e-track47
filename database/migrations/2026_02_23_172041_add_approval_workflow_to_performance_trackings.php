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
        Schema::table('performance_trackings', function (Blueprint $table) {
            // Add approval workflow fields
            $table->timestamp('sector_head_approved_at')->nullable()->after('confirmation_status');
            $table->unsignedBigInteger('sector_head_approved_by')->nullable()->after('sector_head_approved_at');
            $table->timestamp('facilitator_confirmed_at')->nullable()->after('sector_head_approved_by');
            $table->unsignedBigInteger('facilitator_confirmed_by')->nullable()->after('facilitator_confirmed_at');
            $table->timestamp('coordinator_confirmed_at')->nullable()->after('facilitator_confirmed_by');
            $table->unsignedBigInteger('coordinator_confirmed_by')->nullable()->after('coordinator_confirmed_at');
        });

        // Add foreign key constraints with unique names
        Schema::table('performance_trackings', function (Blueprint $table) {
            $table->foreign('sector_head_approved_by', 'fk_perf_track_sector_head_approved_by')
                ->references('id')->on('users')->onDelete('set null');
            $table->foreign('facilitator_confirmed_by', 'fk_perf_track_facilitator_confirmed_by')
                ->references('id')->on('users')->onDelete('set null');
            $table->foreign('coordinator_confirmed_by', 'fk_perf_track_coordinator_confirmed_by')
                ->references('id')->on('users')->onDelete('set null');
        });

        // Update confirmation_status enum to include new workflow statuses
        DB::statement("ALTER TABLE `performance_trackings` MODIFY COLUMN `confirmation_status` ENUM('Not Confirmed', 'Pending Sector Head Approval', 'Pending Facilitator', 'Pending Coordinator', 'Confirmed', 'Rejected') DEFAULT 'Not Confirmed'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performance_trackings', function (Blueprint $table) {
            $table->dropForeign('fk_perf_track_sector_head_approved_by');
            $table->dropForeign('fk_perf_track_facilitator_confirmed_by');
            $table->dropForeign('fk_perf_track_coordinator_confirmed_by');
        });

        Schema::table('performance_trackings', function (Blueprint $table) {
            $table->dropColumn([
                'sector_head_approved_at',
                'sector_head_approved_by',
                'facilitator_confirmed_at',
                'facilitator_confirmed_by',
                'coordinator_confirmed_at',
                'coordinator_confirmed_by'
            ]);
        });

        // Restore original enum
        DB::statement("ALTER TABLE `performance_trackings` MODIFY COLUMN `confirmation_status` ENUM('Confirmed', 'Not Confirmed', 'Rejected') DEFAULT 'Not Confirmed'");
    }
};
