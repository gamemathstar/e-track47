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
