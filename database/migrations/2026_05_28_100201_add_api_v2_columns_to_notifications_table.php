<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive, guarded columns on `notifications` for v2 (§11.14).
 *
 * - kind: wire enum (submission/approval/rejection/discussion/deadline/mention/system).
 * - deep_link_route + deep_link_params: optional tap navigation target.
 *
 * Nullable; web Notification model has no `$fillable`/scopes that would mind.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'kind')) {
                $table->string('kind')->nullable();
            }
            if (! Schema::hasColumn('notifications', 'deep_link_route')) {
                $table->string('deep_link_route')->nullable();
            }
            if (! Schema::hasColumn('notifications', 'deep_link_params')) {
                $table->json('deep_link_params')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            foreach (['kind', 'deep_link_route', 'deep_link_params'] as $col) {
                if (Schema::hasColumn('notifications', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
