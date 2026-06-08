<?php

namespace Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

/**
 * §11.11 role dashboards: each role's snapshot shape + role gating (403).
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPdcuAuth;

    private function seedSector(string $name = 'Health')
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => $name, 'ministry' => 'Ministry of '.$name]);
        $commitment = $this->makeCommitment($sector, ['status' => 'In Progress']);
        $deliverable = $this->makeDeliverable($commitment);
        $kpi = $this->makeKpi($deliverable);
        // Both a target and a tracking row so the KPI satisfies the data-admin
        // dashboard's "fully configured" filter (kpiTargets AND a tracking
        // row for the current quarter/year with a non-empty milestone).
        $t = new \App\Models\KpiTarget();
        $t->kpi_id = $kpi->id;
        $t->year = 2024;
        $t->target = '120';
        $t->save();
        // Tracking matches the dashboard's resolved quarter (calendar fallback
        // when the client doesn't send ?quarter=).
        $currentQuarter = (int) ceil((int) date('n') / 3);
        $this->makeTracking($kpi, [
            'quarter' => $currentQuarter,
            'year' => 2024,
            'actual_value' => '80',
            'milestone' => '100',
        ]);

        return [$sector, $kpi];
    }

    public function test_governor_dashboard(): void
    {
        $this->seedSector();
        Passport::actingAs($this->makeUser(['target_entity' => 'State'], 'Governor'), [], 'api');

        $this->getJson('/api/v2/dashboard/governor')
            ->assertOk()
            ->assertJsonStructure([
                'greeting', 'greetingDate', 'overallPercent', 'overallDeltaLabel',
                'topPerformerName', 'topPerformerPercent', 'topPerformerKpiCount',
                'pendingVerifications', 'totalKpis', 'onTrackCount', 'atRiskCount', 'delayedCount',
                'sectorComparison' => [['sectorId', 'name', 'iconKey', 'accent', 'actualPercent', 'planPercent']],
                'topInsights', 'bottomInsights',
            ])
            ->assertJsonMissingPath('data');
    }

    /** @return array{0:\App\Models\Sector,1:\App\Models\Sector,2:\App\Models\Kpi,3:\App\Models\Kpi} */
    private function seedTwoSectorsWithKpis(): array
    {
        $fw = $this->makeFramework(['year' => 2024]);
        $health = $this->makeSector($fw, ['sector_name' => 'Health']);
        $edu = $this->makeSector($fw, ['sector_name' => 'Education']);
        $hKpi = $this->makeKpi($this->makeDeliverable($this->makeCommitment($health)));
        $eKpi = $this->makeKpi($this->makeDeliverable($this->makeCommitment($edu)));
        $t = new \App\Models\KpiTarget();
        $t->kpi_id = $hKpi->id; $t->year = 2024; $t->target = '120'; $t->save();
        $t2 = new \App\Models\KpiTarget();
        $t2->kpi_id = $eKpi->id; $t2->year = 2024; $t2->target = '120'; $t2->save();

        return [$health, $edu, $hKpi, $eKpi];
    }

    public function test_governor_dashboard_filters_by_sector(): void
    {
        [$health, $edu, $hKpi, $eKpi] = $this->seedTwoSectorsWithKpis();
        // Health hits 90% Q1, Education hits 20% Q1.
        $this->makeTracking($hKpi, ['quarter' => 1, 'year' => 2024, 'actual_value' => '90', 'milestone' => '100']);
        $this->makeTracking($eKpi, ['quarter' => 1, 'year' => 2024, 'actual_value' => '20', 'milestone' => '100']);

        Passport::actingAs($this->makeUser(['target_entity' => 'State'], 'Governor'), [], 'api');

        // State-wide.
        $all = $this->getJson('/api/v2/dashboard/governor')->assertOk()->json();
        $this->assertCount(2, $all['sectorComparison']);

        // Sector-scoped: only Health.
        $h = $this->getJson("/api/v2/dashboard/governor?sector={$health->id}")->assertOk()->json();
        $this->assertCount(1, $h['sectorComparison']);
        $this->assertSame('Health', $h['sectorComparison'][0]['name']);
        $this->assertSame(1, $h['totalKpis']);
        $this->assertSame('Health', $h['topPerformerName']);

        // Sector-scoped: only Education.
        $e = $this->getJson("/api/v2/dashboard/governor?sector={$edu->id}")->assertOk()->json();
        $this->assertCount(1, $e['sectorComparison']);
        $this->assertSame('Education', $e['sectorComparison'][0]['name']);
    }

    public function test_governor_dashboard_filters_by_quarter(): void
    {
        [$health, , $hKpi] = $this->seedTwoSectorsWithKpis();
        // Q1 = 80%, Q3 = 30% for Health KPI.
        $this->makeTracking($hKpi, ['quarter' => 1, 'year' => 2024, 'actual_value' => '80', 'milestone' => '100']);
        $this->makeTracking($hKpi, ['quarter' => 3, 'year' => 2024, 'actual_value' => '30', 'milestone' => '100']);

        Passport::actingAs($this->makeUser(['target_entity' => 'State'], 'Governor'), [], 'api');

        $q1 = $this->getJson("/api/v2/dashboard/governor?sector={$health->id}&quarter=q1")->assertOk()->json();
        $this->assertEqualsWithDelta(80.0, $q1['sectorComparison'][0]['actualPercent'], 0.5);

        $q3 = $this->getJson("/api/v2/dashboard/governor?sector={$health->id}&quarter=q3")->assertOk()->json();
        $this->assertEqualsWithDelta(30.0, $q3['sectorComparison'][0]['actualPercent'], 0.5);

        // Annual (no quarter) → average of the two = ~55%.
        $annual = $this->getJson("/api/v2/dashboard/governor?sector={$health->id}")->assertOk()->json();
        $this->assertEqualsWithDelta(55.0, $annual['sectorComparison'][0]['actualPercent'], 0.5);
    }

    public function test_governor_dashboard_unknown_year_returns_empty_snapshot(): void
    {
        $this->seedTwoSectorsWithKpis();
        Passport::actingAs($this->makeUser(['target_entity' => 'State'], 'Governor'), [], 'api');

        $body = $this->getJson('/api/v2/dashboard/governor?year=2019')->assertOk()->json();
        $this->assertSame(0, $body['totalKpis']);
        $this->assertEqualsWithDelta(0.0, $body['overallPercent'], 0.01);
        $this->assertSame([], $body['sectorComparison']);
        $this->assertSame('—', $body['topPerformerName']);
    }

    public function test_governor_dashboard_unknown_sector_returns_empty_snapshot(): void
    {
        $this->seedTwoSectorsWithKpis();
        Passport::actingAs($this->makeUser(['target_entity' => 'State'], 'Governor'), [], 'api');

        $body = $this->getJson('/api/v2/dashboard/governor?sector=999999')->assertOk()->json();
        $this->assertSame(0, $body['totalKpis']);
        $this->assertSame([], $body['sectorComparison']);
    }

    public function test_governor_dashboard_rejects_unknown_quarter_token(): void
    {
        $this->seedTwoSectorsWithKpis();
        Passport::actingAs($this->makeUser(['target_entity' => 'State'], 'Governor'), [], 'api');

        $this->getJson('/api/v2/dashboard/governor?quarter=q9')
            ->assertStatus(422)
            ->assertJsonStructure(['fieldErrors' => ['quarter']]);
    }

    public function test_coordinator_dashboard(): void
    {
        $this->seedSector();
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson('/api/v2/dashboard/coordinator')
            ->assertOk()
            ->assertJsonStructure(['greeting', 'reviewQueueCount', 'dataEntryOpenSectors', 'submissionRatePercent', 'submissionRateTarget', 'frameworkBadgeLabel', 'frameworkTitle', 'recentSubmissions']);
    }

    public function test_facilitator_dashboard(): void
    {
        [$sector] = $this->seedSector();
        Passport::actingAs($this->makeFacilitator($sector), [], 'api');

        $this->getJson('/api/v2/dashboard/facilitator')
            ->assertOk()
            ->assertJsonStructure(['awaitingReviewCount', 'sectorQueues' => [['sectorId', 'name', 'iconKey', 'lastReviewedLabel', 'awaitingCount']], 'recentDecisions', 'avgResponseDays', 'reviewAccuracyPercent']);
    }

    public function test_facilitator_awaiting_count_derives_from_who_columns_not_status_string(): void
    {
        // Same shape as a row approved via the WEB sector-head flow that
        // forgot to update confirmation_status: WHO columns say "sector head
        // approved, facilitator not yet", but the status string is stale.
        // Mobile must still see it. (Mirrors the production discrepancy where
        // /delivery/tracking/awaiting showed 1 row but /api/v2/dashboard/facilitator
        // reported 0.)
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => 'Agriculture']);
        $commitment = $this->makeCommitment($sector);
        $deliverable = $this->makeDeliverable($commitment);
        $kpi = $this->makeKpi($deliverable);

        $sectorHead = $this->makeSectorHead($sector);
        $this->makeTracking($kpi, [
            'quarter' => 1, 'actual_value' => '80', 'milestone' => '100',
            // WHO columns set as a sector-head approval would set them...
            'sector_head_approved_by' => $sectorHead->id,
            'sector_head_approved_at' => now(),
            'facilitator_confirmed_by' => null,
            'coordinator_confirmed_by' => null,
            // ...but confirmation_status stayed at the pre-approval value.
            'confirmation_status' => 'Pending Sector Head Approval',
        ]);

        Passport::actingAs($this->makeFacilitator($sector), [], 'api');

        $this->getJson('/api/v2/dashboard/facilitator')
            ->assertOk()
            ->assertJsonPath('awaitingReviewCount', 1)
            ->assertJsonPath('sectorQueues.0.awaitingCount', 1);

        $this->getJson('/api/v2/approvals/facilitator/queue?grouping=by_sector')
            ->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Agriculture');
    }

    public function test_sector_head_dashboard(): void
    {
        [$sector] = $this->seedSector();
        Passport::actingAs($this->makeSectorHead($sector), [], 'api');

        $this->getJson('/api/v2/dashboard/sector-head')
            ->assertOk()
            ->assertJsonPath('sectorName', 'My Sector — Health')
            ->assertJsonStructure(['sectorName', 'quarterLabel', 'overallPercent', 'activeKpis', 'totalCommitments', 'completedCommitments', 'inProgressCommitments', 'atRiskCommitments', 'pendingApprovals', 'commitments']);
    }

    public function test_sector_head_dashboard_quarter_filter_scopes_commitment_progress(): void
    {
        // Seed a sector + commitment + deliverable + KPI with a target.
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => 'Health']);
        $commitment = $this->makeCommitment($sector, ['name' => 'Maternal Health']);
        $deliverable = $this->makeDeliverable($commitment);
        $kpi = $this->makeKpi($deliverable);
        $t = new \App\Models\KpiTarget();
        $t->kpi_id = $kpi->id; $t->year = 2024; $t->target = '120';
        $t->save();

        // Two trackings: Q1 hit 80% of milestone, Q3 hit 40%.
        $this->makeTracking($kpi, ['quarter' => 1, 'year' => 2024, 'actual_value' => '80',  'milestone' => '100']);
        $this->makeTracking($kpi, ['quarter' => 3, 'year' => 2024, 'actual_value' => '40',  'milestone' => '100']);

        Passport::actingAs($this->makeSectorHead($sector), [], 'api');

        // ?quarter=q1 → actualPercent reflects Q1's 80%.
        $q1 = $this->getJson('/api/v2/dashboard/sector-head?quarter=q1')->assertOk()->json();
        $this->assertSame('Q1', $q1['quarterLabel']);
        $this->assertEqualsWithDelta(80.0, $q1['commitments'][0]['actualPercent'], 0.01);
        $this->assertEqualsWithDelta(100.0, $q1['commitments'][0]['planPercent'], 0.01);

        // ?quarter=q3 → actualPercent reflects Q3's 40%.
        $q3 = $this->getJson('/api/v2/dashboard/sector-head?quarter=q3')->assertOk()->json();
        $this->assertSame('Q3', $q3['quarterLabel']);
        $this->assertEqualsWithDelta(40.0, $q3['commitments'][0]['actualPercent'], 0.01);

        // ?quarter=q2 → no Q2 row → actualPercent is 0.
        $q2 = $this->getJson('/api/v2/dashboard/sector-head?quarter=q2')->assertOk()->json();
        $this->assertSame('Q2', $q2['quarterLabel']);
        $this->assertEqualsWithDelta(0.0, $q2['commitments'][0]['actualPercent'], 0.01);
    }

    public function test_sector_head_dashboard_default_quarter_is_calendar_quarter(): void
    {
        [$sector] = $this->seedSector();
        Passport::actingAs($this->makeSectorHead($sector), [], 'api');

        $expected = 'Q'.((int) ceil((int) date('n') / 3));

        $this->getJson('/api/v2/dashboard/sector-head')
            ->assertOk()
            ->assertJsonPath('quarterLabel', $expected);
    }

    public function test_sector_head_dashboard_rejects_unknown_quarter_token(): void
    {
        [$sector] = $this->seedSector();
        Passport::actingAs($this->makeSectorHead($sector), [], 'api');

        $this->getJson('/api/v2/dashboard/sector-head?quarter=q9')
            ->assertStatus(422)
            ->assertJsonStructure(['fieldErrors' => ['quarter']]);
    }

    public function test_data_admin_dashboard(): void
    {
        [$sector] = $this->seedSector();
        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $this->getJson('/api/v2/dashboard/data-admin')
            ->assertOk()
            ->assertJsonPath('sectorName', 'Health')
            ->assertJsonStructure(['sectorName', 'quarterLabel', 'completedKpis', 'totalKpis', 'completionPercent', 'deadlines', 'recentActivity']);
    }

    public function test_data_admin_deadlines_show_due_date_when_within_window(): void
    {
        [$sector, $kpi] = $this->seedSector();
        // KPI has no actual_value submission yet → it'll appear in deadlines.
        \App\Models\PerformanceTracking::where('kpi_id', $kpi->id)
            ->update(['actual_value' => null, 'milestone' => '100']);

        $quarter = (int) ceil((int) date('n') / 3);
        $year = (int) (\App\Models\Framework::where('status', 'Active')->first()?->year ?? date('Y'));

        \DB::table('data_entry_accesses')->insert([
            'sector_id' => $sector->id, 'year' => $year, 'quarter' => $quarter,
            'deadline_date' => now()->addDays(7)->toDateString(),
            'override_deadline' => null, 'status' => 'open',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $body = $this->getJson('/api/v2/dashboard/data-admin')->assertOk()->json();
        $this->assertNotEmpty($body['deadlines']);
        $this->assertStringStartsWith('Due ', $body['deadlines'][0]['dueLabel']);
        $this->assertSame('primary', $body['deadlines'][0]['accent']);
        // periodLabel anchors to the Active framework's year, NOT calendar year.
        $this->assertSame("Q{$quarter} {$year}", $body['deadlines'][0]['periodLabel']);
    }

    public function test_data_admin_deadlines_show_extension_when_override_active_after_deadline(): void
    {
        [$sector, $kpi] = $this->seedSector();
        \App\Models\PerformanceTracking::where('kpi_id', $kpi->id)
            ->update(['actual_value' => null, 'milestone' => '100']);

        $quarter = (int) ceil((int) date('n') / 3);
        $year = (int) (\App\Models\Framework::where('status', 'Active')->first()?->year ?? date('Y'));

        \DB::table('data_entry_accesses')->insert([
            'sector_id' => $sector->id, 'year' => $year, 'quarter' => $quarter,
            'deadline_date' => now()->subDays(7)->toDateString(),
            'override_deadline' => now()->addDays(3)->toDateString(),
            'status' => 'override',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $body = $this->getJson('/api/v2/dashboard/data-admin')->assertOk()->json();
        $this->assertStringStartsWith('Extended to ', $body['deadlines'][0]['dueLabel']);
        $this->assertSame('tertiary', $body['deadlines'][0]['accent']);
    }

    public function test_data_admin_deadlines_show_passed_when_deadline_past_and_no_override(): void
    {
        [$sector, $kpi] = $this->seedSector();
        \App\Models\PerformanceTracking::where('kpi_id', $kpi->id)
            ->update(['actual_value' => null, 'milestone' => '100']);

        $quarter = (int) ceil((int) date('n') / 3);
        $year = (int) (\App\Models\Framework::where('status', 'Active')->first()?->year ?? date('Y'));

        \DB::table('data_entry_accesses')->insert([
            'sector_id' => $sector->id, 'year' => $year, 'quarter' => $quarter,
            'deadline_date' => now()->subDays(7)->toDateString(),
            'override_deadline' => null, 'status' => 'closed',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $body = $this->getJson('/api/v2/dashboard/data-admin')->assertOk()->json();
        $this->assertSame('Deadline passed', $body['deadlines'][0]['dueLabel']);
        $this->assertSame('error', $body['deadlines'][0]['accent']);
    }

    public function test_data_admin_deadlines_use_client_supplied_quarter(): void
    {
        [$sector, $kpi] = $this->seedSector();
        \App\Models\PerformanceTracking::where('kpi_id', $kpi->id)
            ->update(['actual_value' => null, 'milestone' => '100']);

        $year = (int) (\App\Models\Framework::where('status', 'Active')->first()?->year ?? date('Y'));

        // Ensure the KPI satisfies the strict filter for both quarters this
        // test queries (Q1 and Q3) — each needs its own tracking row with a
        // non-empty milestone.
        foreach ([1, 3] as $q) {
            \App\Models\PerformanceTracking::firstOrCreate(
                ['kpi_id' => $kpi->id, 'quarter' => $q, 'year' => $year],
                ['framework_id' => $kpi->framework_id, 'milestone' => '100',
                 'actual_value' => null, 'confirmation_status' => 'Not Confirmed'],
            );
        }

        // Seed two different windows: Q1 already passed, Q3 still open. With
        // ?quarter=q3 the dashboard should surface the Q3 deadline, not Q1's.
        \DB::table('data_entry_accesses')->insert([
            'sector_id' => $sector->id, 'year' => $year, 'quarter' => 1,
            'deadline_date' => now()->subDays(60)->toDateString(),
            'override_deadline' => null, 'status' => 'closed',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        \DB::table('data_entry_accesses')->insert([
            'sector_id' => $sector->id, 'year' => $year, 'quarter' => 3,
            'deadline_date' => now()->addDays(20)->toDateString(),
            'override_deadline' => null, 'status' => 'open',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $body = $this->getJson('/api/v2/dashboard/data-admin?quarter=q3')->assertOk()->json();
        $this->assertSame("Q3 {$year}", $body['deadlines'][0]['periodLabel']);
        $this->assertStringStartsWith('Due ', $body['deadlines'][0]['dueLabel']);
        $this->assertSame('primary', $body['deadlines'][0]['accent']);

        // Re-fetch with ?quarter=q1 — same response shape, different window.
        $q1 = $this->getJson('/api/v2/dashboard/data-admin?quarter=q1')->assertOk()->json();
        $this->assertSame("Q1 {$year}", $q1['deadlines'][0]['periodLabel']);
        $this->assertSame('Deadline passed', $q1['deadlines'][0]['dueLabel']);
        $this->assertSame('error', $q1['deadlines'][0]['accent']);
    }

    public function test_data_admin_rejects_unknown_quarter_token(): void
    {
        [$sector] = $this->seedSector();
        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $this->getJson('/api/v2/dashboard/data-admin?quarter=q9')
            ->assertStatus(422)
            ->assertJsonStructure(['fieldErrors' => ['quarter']]);
    }

    public function test_data_admin_dashboard_requires_target_and_quarter_scoped_tracking_with_milestone(): void
    {
        // Five KPIs in the same sector, each one fails a different leg of the
        // filter (or is the fully-configured one). Only the fully-configured
        // KPI should show up.
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => 'Health']);
        $commitment = $this->makeCommitment($sector);
        $deliverable = $this->makeDeliverable($commitment);

        $year = 2024;
        $quarter = 1; // hardcoded; test uses ?quarter=q1 so the dashboard sees this quarter.

        $configured     = $this->makeKpi($deliverable, ['kpi' => 'A — Target + Q1 tracking + milestone']);
        $noTarget       = $this->makeKpi($deliverable, ['kpi' => 'B — No target row']);
        $wrongQuarter   = $this->makeKpi($deliverable, ['kpi' => 'C — Tracking is for Q3, not Q1']);
        $milestoneEmpty = $this->makeKpi($deliverable, ['kpi' => 'D — Milestone is empty string']);
        $milestoneNull  = $this->makeKpi($deliverable, ['kpi' => 'E — Milestone is NULL']);

        // Targets for everyone except $noTarget.
        foreach ([$configured, $wrongQuarter, $milestoneEmpty, $milestoneNull] as $kpi) {
            $t = new \App\Models\KpiTarget();
            $t->kpi_id = $kpi->id;
            $t->year = $year;
            $t->target = '120';
            $t->save();
        }

        // Tracking rows — each fails the filter for a different reason.
        \App\Models\PerformanceTracking::create([
            'kpi_id' => $configured->id, 'framework_id' => $fw->id,
            'quarter' => $quarter, 'year' => $year,
            'milestone' => '100', 'actual_value' => null,
            'confirmation_status' => 'Not Confirmed',
        ]);
        \App\Models\PerformanceTracking::create([
            'kpi_id' => $noTarget->id, 'framework_id' => $fw->id,
            'quarter' => $quarter, 'year' => $year,
            'milestone' => '100', 'actual_value' => null,
            'confirmation_status' => 'Not Confirmed',
        ]);
        \App\Models\PerformanceTracking::create([
            'kpi_id' => $wrongQuarter->id, 'framework_id' => $fw->id,
            'quarter' => 3, 'year' => $year, // wrong quarter
            'milestone' => '100', 'actual_value' => null,
            'confirmation_status' => 'Not Confirmed',
        ]);
        \App\Models\PerformanceTracking::create([
            'kpi_id' => $milestoneEmpty->id, 'framework_id' => $fw->id,
            'quarter' => $quarter, 'year' => $year,
            'milestone' => '', 'actual_value' => null, // empty milestone
            'confirmation_status' => 'Not Confirmed',
        ]);
        \App\Models\PerformanceTracking::create([
            'kpi_id' => $milestoneNull->id, 'framework_id' => $fw->id,
            'quarter' => $quarter, 'year' => $year,
            'milestone' => null, 'actual_value' => null, // null milestone
            'confirmation_status' => 'Not Confirmed',
        ]);

        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $body = $this->getJson('/api/v2/dashboard/data-admin?quarter=q1')
            ->assertOk()->json();

        $this->assertSame(1, $body['totalKpis']);

        $deadlineIds = array_column($body['deadlines'], 'id');
        $this->assertContains((string) $configured->id,     $deadlineIds);
        $this->assertNotContains((string) $noTarget->id,       $deadlineIds);
        $this->assertNotContains((string) $wrongQuarter->id,   $deadlineIds);
        $this->assertNotContains((string) $milestoneEmpty->id, $deadlineIds);
        $this->assertNotContains((string) $milestoneNull->id,  $deadlineIds);
    }

    public function test_data_admin_deadlines_fall_back_when_no_window_configured(): void
    {
        [$sector, $kpi] = $this->seedSector();
        \App\Models\PerformanceTracking::where('kpi_id', $kpi->id)
            ->update(['actual_value' => null, 'milestone' => '100']);
        // No data_entry_accesses row seeded.

        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $body = $this->getJson('/api/v2/dashboard/data-admin')->assertOk()->json();
        $this->assertSame('Due this period', $body['deadlines'][0]['dueLabel']);
        $this->assertSame('primary', $body['deadlines'][0]['accent']);
    }

    public function test_system_admin_dashboard(): void
    {
        $this->seedSector();
        Passport::actingAs($this->makeUser([], 'System Admin'), [], 'api');

        $this->getJson('/api/v2/dashboard/system-admin')
            ->assertOk()
            ->assertJsonStructure(['totalUsers', 'activeUsers', 'revokedUsers', 'userActivePercent', 'loginCount24h', 'galleryImageCount', 'activeFrameworkCount', 'serverHealthPercent', 'apiResponseLabel', 'storageLabel', 'securityRows']);
    }

    public function test_dashboard_is_role_gated(): void
    {
        // A data admin cannot open the governor dashboard.
        [$sector] = $this->seedSector();
        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $this->getJson('/api/v2/dashboard/governor')->assertStatus(403)->assertJsonPath('code', 'forbidden');
    }

    public function test_dashboard_requires_auth(): void
    {
        $this->getJson('/api/v2/dashboard/governor')->assertStatus(401)->assertJsonPath('code', 'unauthenticated');
    }
}
