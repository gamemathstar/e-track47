<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Discussion threads (API_REFERENCE.md §11.15). New table; guarded. sector_id is
 * indexed but not FK-constrained (kept lenient against the SQL-dump-derived
 * production DB, GR5).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('discussion_threads')) {
            return;
        }

        Schema::create('discussion_threads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sector_id')->nullable()->index();
            $table->string('title');
            $table->string('status')->default('in_progress'); // in_progress|resolved|blocked
            $table->string('status_label')->nullable();
            $table->string('lead_name')->nullable();
            $table->string('lead_label')->nullable();
            $table->string('lead_initials', 8)->nullable();
            $table->text('preview_body')->nullable();
            $table->string('author_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_threads');
    }
};
