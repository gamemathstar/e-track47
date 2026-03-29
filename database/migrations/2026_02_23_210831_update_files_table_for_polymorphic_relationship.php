<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if old columns exist and migrate data
        if (Schema::hasColumn('files', 'file_name')) {
            // Use raw SQL for MySQL column renaming
            DB::statement('ALTER TABLE `files` CHANGE COLUMN `file_name` `name` VARCHAR(255) NULL DEFAULT NULL');
            DB::statement('ALTER TABLE `files` CHANGE COLUMN `file_path` `path` VARCHAR(255) NULL DEFAULT NULL');
        }

        // Add new columns for polymorphic relationship
        Schema::table('files', function (Blueprint $table) {
            if (!Schema::hasColumn('files', 'type')) {
                $table->string('type')->nullable()->after('path');
            }
            if (!Schema::hasColumn('files', 'size')) {
                $table->bigInteger('size')->nullable()->after('type');
            }
            if (!Schema::hasColumn('files', 'fileable_id')) {
                $table->unsignedBigInteger('fileable_id')->nullable()->after('size');
            }
            if (!Schema::hasColumn('files', 'fileable_type')) {
                $table->string('fileable_type')->nullable()->after('fileable_id');
            }
            if (!Schema::hasColumn('files', 'attached_by')) {
                $table->string('attached_by')->nullable()->after('fileable_type');
            }
            if (!Schema::hasColumn('files', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            // Drop new columns
            if (Schema::hasColumn('files', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('files', 'size')) {
                $table->dropColumn('size');
            }
            if (Schema::hasColumn('files', 'fileable_id')) {
                $table->dropColumn('fileable_id');
            }
            if (Schema::hasColumn('files', 'fileable_type')) {
                $table->dropColumn('fileable_type');
            }
            if (Schema::hasColumn('files', 'attached_by')) {
                $table->dropColumn('attached_by');
            }
            if (Schema::hasColumn('files', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });

        // Rename columns back if they were renamed
        if (Schema::hasColumn('files', 'name')) {
            DB::statement('ALTER TABLE `files` CHANGE COLUMN `name` `file_name` VARCHAR(255) NULL DEFAULT NULL');
            DB::statement('ALTER TABLE `files` CHANGE COLUMN `path` `file_path` VARCHAR(255) NULL DEFAULT NULL');
        }
    }
};
