<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baseline create for `commitment_budgets`, reconstructed from the live trackerx
 * DDL. Indexed (no DB-level FK) to mirror the live schema. Guarded → no-op on the
 * live DB (GR5).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('commitment_budgets')) {
            return;
        }

        Schema::create('commitment_budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commitment_id')->index();
            $table->integer('year')->index();
            $table->decimal('amount', 10, 0);
            $table->unsignedBigInteger('last_modified_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commitment_budgets');
    }
};
