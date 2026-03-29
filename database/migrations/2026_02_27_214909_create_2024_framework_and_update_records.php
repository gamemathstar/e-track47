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
        // Check if 2024 framework already exists
        $framework2024 = DB::table('frameworks')->where('year', 2024)->first();
        
        if (!$framework2024) {
            // Get the first user or use null
            $firstUser = DB::table('users')->orderBy('id')->first();
            $createdBy = $firstUser ? $firstUser->id : null;
            
            // Create 2024 framework
            $frameworkId = DB::table('frameworks')->insertGetId([
                'year' => 2024,
                'title' => 'Annual Performance Framework 2024',
                'status' => 'Active',
                'description' => 'Performance framework for the year 2024',
                'created_by' => $createdBy,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $frameworkId = $framework2024->id;
        }
        
        // Update all existing records to use the 2024 framework
        // Update sectors
        DB::table('sectors')
            ->whereNull('framework_id')
            ->update(['framework_id' => $frameworkId]);
        
        // Update commitments
        DB::table('commitments')
            ->whereNull('framework_id')
            ->update(['framework_id' => $frameworkId]);
        
        // Update deliverables
        DB::table('deliverables')
            ->whereNull('framework_id')
            ->update(['framework_id' => $frameworkId]);
        
        // Update kpis
        DB::table('kpis')
            ->whereNull('framework_id')
            ->update(['framework_id' => $frameworkId]);
        
        // Update performance_trackings
        DB::table('performance_trackings')
            ->whereNull('framework_id')
            ->update(['framework_id' => $frameworkId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get the 2024 framework ID
        $framework2024 = DB::table('frameworks')->where('year', 2024)->first();
        
        if ($framework2024) {
            // Set framework_id to null for all records that were updated
            DB::table('sectors')
                ->where('framework_id', $framework2024->id)
                ->update(['framework_id' => null]);
            
            DB::table('commitments')
                ->where('framework_id', $framework2024->id)
                ->update(['framework_id' => null]);
            
            DB::table('deliverables')
                ->where('framework_id', $framework2024->id)
                ->update(['framework_id' => null]);
            
            DB::table('kpis')
                ->where('framework_id', $framework2024->id)
                ->update(['framework_id' => null]);
            
            DB::table('performance_trackings')
                ->where('framework_id', $framework2024->id)
                ->update(['framework_id' => null]);
            
            // Optionally delete the framework (commented out to be safe)
            // DB::table('frameworks')->where('year', 2024)->delete();
        }
    }
};
