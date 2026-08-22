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
        // Remove date fields from commitments table (only those that exist).
        // Guarded per-column so a fresh migrate (where some legacy columns such as
        // `duration_in_days` were never created) does not fail. Already-run on the
        // live DB, so this only affects fresh/test builds.
        $commitmentDateCols = array_values(array_filter(
            ['start_date', 'end_date', 'duration_in_days'],
            fn ($col) => Schema::hasColumn('commitments', $col)
        ));
        if ($commitmentDateCols) {
            Schema::table('commitments', function (Blueprint $table) use ($commitmentDateCols) {
                $table->dropColumn($commitmentDateCols);
            });
        }

        // Add year field to kpis table if it doesn't exist
        if (!Schema::hasColumn('kpis', 'year')) {
            Schema::table('kpis', function (Blueprint $table) {
                // Remove date fields if they exist
                if (Schema::hasColumn('kpis', 'start_date')) {
                    $table->dropColumn(['start_date', 'end_date']);
                }
                // Add year field
                if (Schema::hasColumn('kpis', 'unit_of_measurement')) {
                    $table->integer('year')->nullable()->after('unit_of_measurement');
                } else {
                    $table->integer('year')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore date fields to commitments table
        Schema::table('commitments', function (Blueprint $table) {
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('duration_in_days')->nullable();
        });

        // Restore date fields to kpis table and remove year field
        Schema::table('kpis', function (Blueprint $table) {
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->dropColumn('year');
        });
    }
};
