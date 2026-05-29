<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user app preferences (API_REFERENCE.md §11.12.1/2). Brand-new table;
 * guarded. user_id is unique. The web app does not read this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_settings')) {
            return;
        }

        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('theme_mode')->default('system'); // system|light|dark
            $table->decimal('font_scale', 3, 2)->default(0.5); // 0–1 slider position
            $table->boolean('biometric_enabled')->default(false);
            $table->boolean('cellular_uploads_enabled')->default(false);
            $table->boolean('sync_on_wifi_only')->default(true);
            $table->string('language_code', 10)->default('en-NG');
            $table->string('language_label')->default('English (Nigeria)');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
