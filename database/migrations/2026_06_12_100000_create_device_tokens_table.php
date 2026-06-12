<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-device FCM registration store (API_REFERENCE.md §11.14).
 *
 * Supersedes users.fcm_token, which only allowed one device per user. Each
 * mobile / web client registers its current FCM token after login (and on
 * every rotation); logout calls DELETE to remove it. Tokens are unique across
 * the table — if the same handset reinstalls / re-pairs with a different user
 * the older row is overwritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('device_tokens')) {
            return;
        }

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token', 512)->unique();
            $table->string('platform', 16)->default('android'); // ios | android | web
            $table->string('app_version', 32)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
