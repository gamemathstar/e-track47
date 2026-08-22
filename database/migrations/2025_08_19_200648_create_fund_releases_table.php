<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baseline create for `fund_releases`, reconstructed from the live trackerx DDL
 * (which carries a FK to commitments). Placed after the commitments create so the
 * FK resolves on a fresh DB. Guarded → no-op on the live DB (GR5).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fund_releases')) {
            return;
        }

        Schema::create('fund_releases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commitment_id')->nullable()->index();
            $table->date('release_date')->nullable();
            $table->decimal('released_amount', 15, 2)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('commitment_id')->references('id')->on('commitments');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_releases');
    }
};
