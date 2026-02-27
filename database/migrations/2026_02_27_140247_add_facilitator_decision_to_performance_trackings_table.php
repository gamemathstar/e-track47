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
        Schema::table('performance_trackings', function (Blueprint $table) {
            $table->enum('facilitator_decision', ['Accept', 'Reject'])->nullable()->after('facilitator_confirmed_by');
            $table->text('facilitator_rejection_reason')->nullable()->after('facilitator_decision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performance_trackings', function (Blueprint $table) {
            $table->dropColumn(['facilitator_decision', 'facilitator_rejection_reason']);
        });
    }
};
