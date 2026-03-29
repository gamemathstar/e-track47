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
        Schema::table('deliverables', function (Blueprint $table) {
            $table->foreignId('framework_id')->nullable()->after('commitment_id')->constrained('frameworks')->onDelete('cascade');
            $table->index('framework_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliverables', function (Blueprint $table) {
            $table->dropForeign(['framework_id']);
            $table->dropIndex(['framework_id']);
            $table->dropColumn('framework_id');
        });
    }
};
