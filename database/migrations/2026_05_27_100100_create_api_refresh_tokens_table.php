<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rotating opaque refresh tokens for the v2 mobile API (assumption A1).
 *
 * New table — no impact on the web app or v1. Stores only the token hash. The
 * user_id FK is intentionally NOT a DB-level constraint to stay safe against the
 * SQL-dump-derived schema (GR5); referential integrity is enforced in the model
 * layer. Guarded with hasTable so re-runs are safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('api_refresh_tokens')) {
            return;
        }

        Schema::create('api_refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('device_label')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_refresh_tokens');
    }
};
