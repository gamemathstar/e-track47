<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baseline create for `sector_budgets`, reconstructed from the live trackerx DDL.
 * Placed after sectors/commitments creates so references resolve on a fresh DB.
 * Indexed (no DB-level FK) to mirror the live schema. Guarded → no-op on the live
 * DB (GR5).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sector_budgets')) {
            return;
        }

        Schema::create('sector_budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sector_id')->index();
            $table->integer('year');
            $table->string('amount', 64);
            $table->unsignedBigInteger('last_modified_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sector_budgets');
    }
};
