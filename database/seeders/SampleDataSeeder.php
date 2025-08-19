<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sector;
use App\Models\Commitment;
use App\Models\Deliverable;
use App\Models\Kpi;
use App\Models\KpiTarget;
use App\Models\PerformanceTracking;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample sector
        $sector = Sector::create([
            'sector_name' => 'Office of the Head of Service',
            'description' => 'Public Service Reforms and Service Delivery Improvement Initiatives',
            'code' => 'OHOS',
            'ministry' => 'Office of the Head of Service',
            'status' => 'active'
        ]);

        // Create sample commitment
        $commitment = Commitment::create([
            'sector_id' => $sector->id,
            'name' => 'Sustain Improvement of Public Service Delivery through an optimized State Civil Service',
            'description' => 'Improve public service delivery through rewards and sanctions',
            'status' => 'active',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31'
        ]);

        // Create sample deliverable
        $deliverable = Deliverable::create([
            'commitment_id' => $commitment->id,
            'deliverable' => 'Enhanced Personnel and Organizational Productivity',
            'description' => 'Improve productivity through mandates, job descriptions and performance targets',
            'status' => 'active',
            'due_date' => '2024-12-31'
        ]);

        // Create sample KPI
        $kpi = Kpi::create([
            'deliverable_id' => $deliverable->id,
            'kpi' => 'Organisational Productivity Level',
            'description' => 'Measure of organizational productivity',
            'unit_of_measurement' => 'Percentage',
            'status' => 'active'
        ]);

        // Create sample KPI target
        KpiTarget::create([
            'kpi_id' => $kpi->id,
            'year' => 2024,
            'target' => 70
        ]);

        // Create sample performance tracking
        PerformanceTracking::create([
            'kpi_id' => $kpi->id,
            'quarter' => 1,
            'milestone' => 'Q1 Milestone',
            'year' => 2024,
            'actual_value' => '65',
            'remarks' => 'Q1 performance tracking completed',
            'confirmation_status' => 'Confirmed'
        ]);

        PerformanceTracking::create([
            'kpi_id' => $kpi->id,
            'quarter' => 2,
            'milestone' => 'Q2 Milestone',
            'year' => 2024,
            'actual_value' => '72',
            'remarks' => 'Q2 performance tracking completed',
            'confirmation_status' => 'Confirmed'
        ]);

        // Create another sector for testing
        $sector2 = Sector::create([
            'sector_name' => 'Ministry of Health',
            'description' => 'Healthcare and Medical Services',
            'code' => 'MOH',
            'ministry' => 'Ministry of Health',
            'status' => 'active'
        ]);

        $commitment2 = Commitment::create([
            'sector_id' => $sector2->id,
            'name' => 'Improve Healthcare Delivery and Access',
            'description' => 'Enhance healthcare services across the state',
            'status' => 'active',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31'
        ]);

        $deliverable2 = Deliverable::create([
            'commitment_id' => $commitment2->id,
            'deliverable' => 'Healthcare Infrastructure Development',
            'description' => 'Build and upgrade healthcare facilities',
            'status' => 'active',
            'due_date' => '2024-12-31'
        ]);

        $kpi2 = Kpi::create([
            'deliverable_id' => $deliverable2->id,
            'kpi' => 'Number of Healthcare Facilities',
            'description' => 'Count of operational healthcare facilities',
            'unit_of_measurement' => 'Number',
            'status' => 'active'
        ]);

        KpiTarget::create([
            'kpi_id' => $kpi2->id,
            'year' => 2024,
            'target' => 50
        ]);

        PerformanceTracking::create([
            'kpi_id' => $kpi2->id,
            'quarter' => 1,
            'milestone' => 'Q1 Target',
            'year' => 2024,
            'actual_value' => '45',
            'remarks' => 'Q1 healthcare facilities count',
            'confirmation_status' => 'Confirmed'
        ]);
    }
}
