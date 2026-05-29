<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Discussion comments (API_REFERENCE.md §11.15.3). Self-referencing parent_id
 * supports a single level of replies. like_count is a denormalized counter kept
 * in sync by the toggle endpoint. user_id linking is optional (kept lenient).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('discussion_comments')) {
            return;
        }

        Schema::create('discussion_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('thread_id')->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('author_name');
            $table->string('author_role')->nullable();
            $table->string('author_initials', 8)->nullable();
            $table->text('body');
            $table->unsignedInteger('like_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_comments');
    }
};
