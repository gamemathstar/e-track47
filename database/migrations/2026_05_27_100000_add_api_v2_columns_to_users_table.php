<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive, guarded columns on `users` for the v2 mobile API.
 *
 * - must_change_password: gates the forced password-change flow (API §11.1.4).
 * - avatar_key: optional built-in avatar id chosen at user creation (§11.9.3).
 *
 * Both are nullable / defaulted, so they are inert to the existing web app and
 * v1 API (verified: no web reference to these columns). Guarded with hasColumn
 * so it is safe against the SQL-dump-derived production schema (GR5).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false);
            }
            if (! Schema::hasColumn('users', 'avatar_key')) {
                $table->string('avatar_key')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            foreach (['must_change_password', 'avatar_key'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
