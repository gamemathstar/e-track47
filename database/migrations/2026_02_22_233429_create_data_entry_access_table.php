<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('data_entry_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_id')->constrained('sectors')->onDelete('cascade');
            $table->integer('year')->notNull();
            $table->integer('quarter')->notNull(); // 1, 2, 3, or 4
            $table->date('deadline_date')->notNull(); // Normal deadline (2 weeks after quarter end)
            $table->enum('status', ['open', 'closed', 'override'])->default('closed');
            $table->date('override_deadline')->nullable(); // Custom deadline if override is granted
            $table->text('override_reason')->nullable(); // Reason for override
            $table->foreignId('granted_by')->nullable()->constrained('users')->onDelete('set null'); // User who granted override
            $table->timestamp('granted_at')->nullable(); // When override was granted
            $table->timestamps();
            
            // Ensure one access record per sector/quarter/year combination
            $table->unique(['sector_id', 'year', 'quarter'], 'unique_sector_quarter_year');
            
            // Indexes for performance
            $table->index(['year', 'quarter']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_entry_access');
    }
};
