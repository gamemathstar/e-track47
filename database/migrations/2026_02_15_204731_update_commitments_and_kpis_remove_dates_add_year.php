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
        // Remove date fields from commitments table
        Schema::table('commitments', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'duration_in_days']);
        });

        // Remove date fields from kpis table and add year field
        Schema::table('kpis', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
            $table->integer('year')->nullable()->after('unit_of_measurement');
        });
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
