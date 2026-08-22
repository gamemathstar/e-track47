<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baseline create for `notifications`, shaped for the PDCU Notification model.
 *
 * NOTE: the live trackerx `notifications` table is a leftover from a *different*
 * application (its `type` is an enum of payment/pocket events), so we deliberately
 * reconstruct the PDCU-correct shape here ("correct to intended schema") with a
 * free-form string `type`, which is what App\Models\Notification actually writes.
 * Guarded with hasTable → no-op on the live DB, so the (mismatched) production
 * table is left untouched (GR5); this is a separate data-hygiene issue flagged for
 * the team.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('sender_id')->default(0);
            $table->string('type')->index();
            $table->string('title');
            $table->text('body');
            $table->unsignedBigInteger('model_id')->default(0)->index();
            $table->enum('status', ['Read', 'Not Read'])->default('Not Read')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
