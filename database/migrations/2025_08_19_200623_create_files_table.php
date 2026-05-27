<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baseline create for `files` (polymorphic evidence attachments), in its FINAL
 * shape, reconstructed from the live trackerx DDL. Placed before the existing
 * `update_files_table_for_polymorphic` ALTER (2026_02_23_210831), which is fully
 * guarded (it only renames `file_name` if present and only adds columns if
 * missing) — so with the table already in final shape that ALTER is a safe no-op.
 *
 * Guarded with hasTable → no-op on the live DB (GR5).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('files')) {
            return;
        }

        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('path')->nullable();
            $table->string('type')->nullable();
            $table->bigInteger('size')->nullable();
            $table->unsignedBigInteger('fileable_id')->nullable();
            $table->string('fileable_type')->nullable();
            $table->string('attached_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
