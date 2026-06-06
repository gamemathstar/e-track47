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
        // dashboard's "fully configured" filter (kpiTargets AND performanceTracking).
        $t = new \App\Models\KpiTarget();
        $t->kpi_id = $kpi->id;
        $t->year = 2024;
        $t->target = '120';
        $t->save();
        $this->makeTracking($kpi, ['quarter' => 1, 'actual_value' => '80', 'milestone' => '100']);

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
            ->assertJsonStructure(['sectorName', 'overallPercent', 'activeKpis', 'totalCommitments', 'completedCommitments', 'inProgressCommitments', 'atRiskCommitments', 'pendingApprovals', 'commitments']);
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

    public function test_data_admin_dashboard_requires_both_target_and_tracking(): void
    {
        // Four KPIs in the same sector, one per state in the truth table.
        // Only the fully-configured one (target AND tracking) is expected to
        // appear; the other three are excluded.
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => 'Health']);
        $commitment = $this->makeCommitment($sector);
        $deliverable = $this->makeDeliverable($commitment);

        $bothSet      = $this->makeKpi($deliverable, ['kpi' => 'A — Target + Tracking']);
        $targetOnly   = $this->makeKpi($deliverable, ['kpi' => 'B — Target only']);
        $trackingOnly = $this->makeKpi($deliverable, ['kpi' => 'C — Tracking only']);
        $neither      = $this->makeKpi($deliverable, ['kpi' => 'D — Neither']);

        // Target rows for $bothSet and $targetOnly.
        foreach ([$bothSet->id, $targetOnly->id] as $id) {
            $t = new \App\Models\KpiTarget();
            $t->kpi_id = $id;
            $t->year = 2024;
            $t->target = '120';
            $t->save();
        }

        // Tracking rows for $bothSet and $trackingOnly.
        foreach ([$bothSet->id, $trackingOnly->id] as $id) {
            \App\Models\PerformanceTracking::create([
                'kpi_id' => $id, 'framework_id' => $fw->id,
                'quarter' => 1, 'year' => 2024,
                'milestone' => '100', 'actual_value' => null,
                'confirmation_status' => 'Not Confirmed',
            ]);
        }

        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $body = $this->getJson('/api/v2/dashboard/data-admin')->assertOk()->json();

        // Only the fully-configured KPI is counted.
        $this->assertSame(1, $body['totalKpis']);

        $deadlineIds = array_column($body['deadlines'], 'id');
        $this->assertContains((string) $bothSet->id, $deadlineIds);
        $this->assertNotContains((string) $targetOnly->id,   $deadlineIds);
        $this->assertNotContains((string) $trackingOnly->id, $deadlineIds);
        $this->assertNotContains((string) $neither->id,      $deadlineIds);
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
