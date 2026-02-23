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
        Schema::create('galleries', function (Blueprint $table) {
            $table->id();
            $table->string('image_path'); // Path to the uploaded image
            $table->string('title')->nullable(); // Optional title for the image
            $table->text('caption')->nullable(); // Description/caption for the image
            $table->enum('status', ['active', 'inactive'])->default('active'); // Status to show/hide images
            $table->integer('display_order')->default(0); // Order for sorting/display
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null'); // User who uploaded the image
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('galleries');
    }
};
