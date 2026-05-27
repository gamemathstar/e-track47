<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baseline create for `users`, reconstructed from the live trackerx DDL
 * (SHOW CREATE TABLE) — NOT from the SQL dump file.
 *
 * Why this exists: the original users create-migration is misplaced under
 * database/factories/ (and defines the wrong, default-Laravel `name` schema), so
 * the migrator never ran it and `migrate:fresh` could not build the real users
 * table. This guarded migration fills that gap for clean (test) databases.
 *
 * Guarded with hasTable: on the live/production DB (where `users` already exists)
 * it is a safe no-op, so it cannot affect the running web app (GR5).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 222);
            $table->string('email', 222)->unique();
            $table->string('phone_number', 32)->default('');
            $table->integer('role')->default(0); // legacy scalar role; real roles live in user_roles
            $table->string('password', 233);
            $table->string('image_url', 233)->default('');
            $table->text('token')->nullable();
            $table->text('fcm_token')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
