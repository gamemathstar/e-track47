<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Schema relaxations needed for the v2 public POST /gallery/items/:id/comments
 * endpoint (anonymous submitters provide only authorName + body):
 *
 *   - Add `status` (pending|approved|rejected) so v2 submissions can be held
 *     for moderation before surfacing on the public detail view.
 *   - Make `phone_number` nullable — the v2 contract doesn't include it. The
 *     existing web form still sends it, so this is purely additive.
 *
 * Existing rows are backfilled to `status = 'approved'` so the public detail
 * view doesn't suddenly hide every historical comment.
 *
 * Guarded with `Schema::hasColumn` so re-running is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gallery_comments')) {
            return;
        }

        // 1. status (default 'approved' so legacy web submissions stay
        //    auto-approved; v2 service explicitly inserts 'pending').
        if (! Schema::hasColumn('gallery_comments', 'status')) {
            Schema::table('gallery_comments', function (Blueprint $table) {
                $table->string('status', 20)->default('approved')->index();
            });
            // Backfill any pre-existing rows (defensive; default already covers
            // new inserts, but this handles older DBs that ran before the
            // default kicked in).
            DB::table('gallery_comments')->whereNull('status')->update(['status' => 'approved']);
        }

        // 2. phone_number → nullable. Using a raw ALTER because Doctrine's
        //    Laravel column-change requires doctrine/dbal which this project
        //    doesn't pull in.
        DB::statement('ALTER TABLE `gallery_comments` MODIFY `phone_number` VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('gallery_comments')) {
            return;
        }
        if (Schema::hasColumn('gallery_comments', 'status')) {
            Schema::table('gallery_comments', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
        // Revert phone_number back to NOT NULL — only safe if no NULLs exist.
        DB::statement("UPDATE `gallery_comments` SET `phone_number` = '' WHERE `phone_number` IS NULL");
        DB::statement('ALTER TABLE `gallery_comments` MODIFY `phone_number` VARCHAR(255) NOT NULL');
    }
};
