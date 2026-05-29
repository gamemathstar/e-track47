<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user notification preferences (API_REFERENCE.md §11.14). Brand-new table;
 * guarded; web app does not touch it. user_id is enforced as unique so each user
 * has at most one row.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_preferences')) {
            return;
        }

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            // 5 category toggles
            $table->boolean('submissions')->default(true);
            $table->boolean('approvals')->default(true);
            $table->boolean('rejections')->default(true);
            $table->boolean('mentions')->default(true);
            $table->boolean('deadlines')->default(true);
            // 3 channel toggles
            $table->boolean('push')->default(true);
            $table->boolean('email')->default(true);
            $table->boolean('sms')->default(false);
            // quiet hours
            $table->boolean('quiet_hours_enabled')->default(false);
            $table->unsignedTinyInteger('quiet_from_hour')->default(22);
            $table->unsignedTinyInteger('quiet_from_minute')->default(0);
            $table->unsignedTinyInteger('quiet_to_hour')->default(6);
            $table->unsignedTinyInteger('quiet_to_minute')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
