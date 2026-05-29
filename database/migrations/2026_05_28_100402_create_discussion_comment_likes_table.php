<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user comment likes (API_REFERENCE.md §11.15.5). Unique (comment_id,
 * user_id) enforces idempotent like state; toggle inserts or deletes the row.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('discussion_comment_likes')) {
            return;
        }

        Schema::create('discussion_comment_likes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comment_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamps();
            $table->unique(['comment_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_comment_likes');
    }
};
