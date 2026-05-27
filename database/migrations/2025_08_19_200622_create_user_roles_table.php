<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baseline create for `user_roles`, reconstructed from the live trackerx DDL.
 * No create-migration existed in the repo (only later ALTERs), so `migrate:fresh`
 * failed once those ALTERs ran. The enum is authored with the FINAL role set so
 * the later enum-modify / rename ALTERs are safe re-applies on an empty table.
 *
 * Guarded with hasTable → no-op on the live DB (GR5).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_roles')) {
            return;
        }

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->enum('role', [
                'Governor', 'Sector Head', 'Data Admin', 'Coordinator',
                'Deputy Coordinator', 'Facilitator', 'System Admin',
            ]);
            $table->enum('target_entity', ['System', 'State', 'Sector', 'Project', 'Deliverable']);
            $table->bigInteger('entity_id')->default(0)->comment('0 for all');
            $table->enum('role_status', ['Active', 'Revoked']);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
