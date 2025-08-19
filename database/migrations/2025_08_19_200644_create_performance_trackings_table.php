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
        Schema::create('performance_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_id')->constrained()->onDelete('cascade');
            $table->integer('quarter');
            $table->string('milestone');
            $table->date('tracking_date')->nullable();
            $table->integer('year');
            $table->string('actual_value')->nullable();
            $table->text('remarks')->nullable();
            $table->string('delivery_department_value')->nullable();
            $table->text('delivery_department_remark')->nullable();
            $table->enum('confirmation_status', ['Confirmed', 'Not Confirmed', 'Rejected'])->default('Not Confirmed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_trackings');
    }
};
