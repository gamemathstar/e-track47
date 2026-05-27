<?php

namespace Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

/**
 * §11.3 sector → commitment → deliverable read hierarchy: response shapes,
 * derived metrics, role-based scoping, and auth.
 */
class HierarchyTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPdcuAuth;

    public function test_list_sectors_returns_raw_array_with_required_shape(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => 'Health', 'ministry' => 'Ministry of Health']);
        $this->makeCommitment($sector, ['status' => 'Completed']);
        $this->makeCommitment($sector, ['status' => 'At Risk']);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson('/api/v2/sectors')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonStructure([['id', 'name', 'ministry', 'icon', 'progressPercent', 'completedCommitments', 'atRiskCommitments']])
            ->assertJsonPath('0.name', 'Health')
            ->assertJsonPath('0.icon', 'medical_services')
            ->assertJsonPath('0.completedCommitments', 1)
            ->assertJsonPath('0.atRiskCommitments', 1)
            // list items omit the detail-only fields
            ->assertJsonMissingPath('0.pendingApprovals');
    }

    public function test_sector_detail_includes_detail_only_fields_and_progress(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw);
        $commitment = $this->makeCommitment($sector, ['status' => 'In Progress']);
        $deliverable = $this->makeDeliverable($commitment);
        $kpi = $this->makeKpi($deliverable);
        // 80/100 achievement → progress 0.8
        $this->makeTracking($kpi, ['milestone' => '100', 'actual_value' => '80']);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson("/api/v2/sectors/{$sector->id}")
            ->assertOk()
            ->assertJsonPath('id', (string) $sector->id)
            ->assertJsonPath('totalCommitments', 1)
            ->assertJsonPath('inProgressCommitments', 1)
            ->assertJsonPath('pendingApprovals', 1)
            ->assertJsonPath('progressPercent', 0.8);
    }

    public function test_commitments_and_deliverables_drilldown(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw);
        $commitment = $this->makeCommitment($sector, ['name' => 'Maternal Health Expansion']);
        $deliverable = $this->makeDeliverable($commitment, ['deliverable' => 'Clinic Digitization']);
        $this->makeKpi($deliverable);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson("/api/v2/sectors/{$sector->id}/commitments")
            ->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Maternal Health Expansion')
            ->assertJsonPath('0.sectorId', (string) $sector->id)
            ->assertJsonPath('0.kpiCount', 1)
            ->assertJsonPath('0.status', 'on_track');

        $this->getJson("/api/v2/commitments/{$commitment->id}")
            ->assertOk()
            ->assertJsonPath('deliverableCount', 1)
            ->assertJsonPath('completionStatus', '0 of 1');

        $this->getJson("/api/v2/commitments/{$commitment->id}/deliverables")
            ->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Clinic Digitization')
            ->assertJsonPath('0.commitmentId', (string) $commitment->id)
            ->assertJsonPath('0.parentCommitmentTitle', 'Maternal Health Expansion');

        $this->getJson("/api/v2/deliverables/{$deliverable->id}")
            ->assertOk()
            ->assertJsonPath('id', (string) $deliverable->id)
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('kpiCount', 1);
    }

    public function test_sector_head_only_sees_own_sector(): void
    {
        $fw = $this->makeFramework();
        $own = $this->makeSector($fw, ['sector_name' => 'Health']);
        $other = $this->makeSector($fw, ['sector_name' => 'Education', 'ministry' => 'Ministry of Education']);

        Passport::actingAs($this->makeSectorHead($own), [], 'api');

        $this->getJson('/api/v2/sectors')
            ->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.id', (string) $own->id);

        // The other sector is invisible → 404 (no existence leak).
        $this->getJson("/api/v2/sectors/{$other->id}")->assertStatus(404)->assertJsonPath('code', 'not_found');
    }

    public function test_unknown_sector_returns_404(): void
    {
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');
        $this->getJson('/api/v2/sectors/999999')->assertStatus(404)->assertJsonPath('code', 'not_found');
    }

    public function test_hierarchy_requires_authentication(): void
    {
        $this->getJson('/api/v2/sectors')->assertStatus(401)->assertJsonPath('code', 'unauthenticated');
    }
}
