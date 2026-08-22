<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive, guarded columns on `galleries` for the v2 mobile gallery (§11.13).
 *
 * - category: bucket key (infrastructure, education, health, agriculture).
 * - is_public: visibility flag for the public gallery list (defaults true).
 * - icon_key: client icon slot.
 * - gradient_keys: JSON array of accent slots.
 *
 * All nullable / defaulted; web app reads `status` only, so this is inert.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('galleries')) {
            return;
        }

        Schema::table('galleries', function (Blueprint $table) {
            if (! Schema::hasColumn('galleries', 'category')) {
                $table->string('category')->nullable();
            }
            if (! Schema::hasColumn('galleries', 'is_public')) {
                $table->boolean('is_public')->default(true);
            }
            if (! Schema::hasColumn('galleries', 'icon_key')) {
                $table->string('icon_key')->nullable();
            }
            if (! Schema::hasColumn('galleries', 'gradient_keys')) {
                $table->json('gradient_keys')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('galleries')) {
            return;
        }

        Schema::table('galleries', function (Blueprint $table) {
            foreach (['category', 'is_public', 'icon_key', 'gradient_keys'] as $column) {
                if (Schema::hasColumn('galleries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
