<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('performance_trackings', function (Blueprint $table) {
            $table->enum('coordinator_decision', ['Accept', 'Reject'])->nullable()->after('coordinator_confirmed_by');
            $table->text('coordinator_rejection_reason')->nullable()->after('coordinator_decision');
        });
    }

    public function down(): void
    {
        Schema::table('performance_trackings', function (Blueprint $table) {
            $table->dropColumn(['coordinator_decision', 'coordinator_rejection_reason']);
        });
    }
};
