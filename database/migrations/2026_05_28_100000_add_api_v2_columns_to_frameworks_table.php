<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive, guarded columns on `frameworks` for the v2 mobile API (§11.5).
 *
 * - subtitle: small label shown under the title.
 * - is_default: mirror of "the active framework" so the wire `isDefault` works.
 *   The web app keys off `status='Active'` exclusively — v2's `set-default`
 *   reuses the web's activate semantics (status='Active' + archive others) and
 *   keeps `is_default` in sync (GR2).
 * - inherited_from_framework_id: source framework when created by inheritance.
 *
 * All nullable / defaulted, so the web app is unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('frameworks')) {
            return;
        }

        Schema::table('frameworks', function (Blueprint $table) {
            if (! Schema::hasColumn('frameworks', 'subtitle')) {
                $table->string('subtitle')->nullable();
            }
            if (! Schema::hasColumn('frameworks', 'is_default')) {
                $table->boolean('is_default')->default(false);
            }
            if (! Schema::hasColumn('frameworks', 'inherited_from_framework_id')) {
                $table->unsignedBigInteger('inherited_from_framework_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('frameworks')) {
            return;
        }

        Schema::table('frameworks', function (Blueprint $table) {
            foreach (['subtitle', 'is_default', 'inherited_from_framework_id'] as $column) {
                if (Schema::hasColumn('frameworks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
