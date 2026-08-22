<?php

namespace Tests\Feature\Api\V2;

use App\Models\PerformanceTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

/**
 * End-to-end happy path across features (F3/F4/F5/F8) — proves the same
 * PerformanceTracking row drives KPI tracking, the four-stage approval workflow,
 * and the downstream dashboard / reports surfaces. If any of these contracts
 * drift in isolation, this catches the regression.
 */
class EndToEndWorkflowTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPdcuAuth;

    public function test_kpi_lifecycle_from_milestone_to_dashboard(): void
    {
        $this->seedPersonalAccessClient();

        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => 'Health', 'ministry' => 'Ministry of Health']);
        $commitment = $this->makeCommitment($sector, ['name' => 'Maternal Health Expansion']);
        $deliverable = $this->makeDeliverable($commitment);
        $kpi = $this->makeKpi($deliverable, ['kpi' => 'Clinics with EHR', 'unit_of_measurement' => '%']);
        $this->makeKpiTarget($kpi, 85, 2024);

        $coordinator = $this->makeUser([], 'Coordinator');
        $sectorHead = $this->makeSectorHead($sector);
        $dataAdmin = $this->makeDataAdmin($sector);
        $facilitator = $this->makeFacilitator($sector);

        // === 1) Coordinator sets the Q1 milestone ===
        Passport::actingAs($coordinator, [], 'api');
        $this->postJson("/api/v2/kpis/{$kpi->id}/milestones", [
            'quarter' => 'q1', 'year' => 2024, 'value' => '100',
        ])->assertStatus(202);

        $tracking = PerformanceTracking::where('kpi_id', $kpi->id)->where('quarter', 1)->first();
        $this->assertNotNull($tracking, 'Milestone should create the tracking row');
        $this->assertSame('100', $tracking->milestone);

        // === 2) Data Admin submits actual value ===
        Passport::actingAs($dataAdmin, [], 'api');
        $this->postJson("/api/v2/kpis/{$kpi->id}/submissions", [
            'quarter' => 'q1', 'actualValue' => '85', 'evidenceDocumentIds' => [], 'remarks' => 'Q1 done',
        ])->assertStatus(202);

        $tracking->refresh();
        $this->assertSame('85', $tracking->actual_value);
        $this->assertSame('Pending Sector Head Approval', $tracking->confirmation_status);

        // === 3) Sector Head approves ===
        Passport::actingAs($sectorHead, [], 'api');
        $this->postJson("/api/v2/approvals/submissions/{$tracking->id}/review", [
            'role' => 'sector_head', 'decision' => 'accept',
        ])->assertStatus(202);

        $tracking->refresh();
        $this->assertSame('Pending Facilitator', $tracking->confirmation_status);
        $this->assertSame($sectorHead->id, (int) $tracking->sector_head_approved_by);

        // === 4) Facilitator accepts (with validated value) ===
        Passport::actingAs($facilitator, [], 'api');
        $this->postJson("/api/v2/approvals/submissions/{$tracking->id}/review", [
            'role' => 'facilitator', 'decision' => 'accept', 'validatedValue' => '85', 'acceptRemarks' => 'verified',
        ])->assertStatus(202);

        $tracking->refresh();
        $this->assertSame('Pending Coordinator', $tracking->confirmation_status);
        $this->assertSame('Accept', $tracking->facilitator_decision);
        $this->assertSame('85', $tracking->delivery_department_value);

        // === 5) Coordinator confirms ===
        Passport::actingAs($coordinator, [], 'api');
        $this->postJson("/api/v2/approvals/submissions/{$tracking->id}/review", [
            'role' => 'coordinator', 'decision' => 'accept',
        ])->assertStatus(202);

        $tracking->refresh();
        $this->assertSame('Confirmed', $tracking->confirmation_status);

        // === 6) Downstream surfaces reflect the confirmed submission ===

        // KPI detail (F3): quarter 1 → completed; status moves off pending.
        $detail = $this->getJson("/api/v2/kpis/{$kpi->id}")->assertOk()->json();
        $this->assertSame('completed', $detail['quartersOverview'][0]);
        $this->assertContains($detail['status'], ['active', 'stable']);

        // Coordinator dashboard (F5): no longer awaiting review for this KPI.
        $cDash = $this->getJson('/api/v2/dashboard/coordinator')->assertOk()->json();
        $this->assertSame(0, $cDash['reviewQueueCount']);

        // Reports hub (F12): pendingCount = 0; topSectorLabel = Health.
        $hub = $this->getJson('/api/v2/reports/hub')->assertOk()->json();
        $this->assertSame(0, $hub['pendingCount']);
        $this->assertSame('Health', $hub['topSectorLabel']);

        // Reports viewer (F12): the KPI appears in the Health group.
        $viewer = $this->postJson('/api/v2/reports/viewer', ['sectorIds' => [], 'year' => 2024, 'includeEvidence' => true])
            ->assertOk()->json();
        $this->assertSame('Health', $viewer['groups'][0]['label']);
        $this->assertSame('Clinics with EHR', $viewer['groups'][0]['kpiRows'][0]['title']);

        // Confirmed quarter is locked — resubmission is 409.
        Passport::actingAs($dataAdmin, [], 'api');
        $this->postJson("/api/v2/kpis/{$kpi->id}/submissions", [
            'quarter' => 'q1', 'actualValue' => '90', 'evidenceDocumentIds' => [],
        ])->assertStatus(409)->assertJsonPath('code', 'conflict');
    }

    public function test_rejection_path_returns_to_data_admin(): void
    {
        $this->seedPersonalAccessClient();

        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw);
        $commitment = $this->makeCommitment($sector);
        $deliverable = $this->makeDeliverable($commitment);
        $kpi = $this->makeKpi($deliverable);
        $this->makeKpiTarget($kpi, 85, 2024);

        $coordinator = $this->makeUser([], 'Coordinator');
        $sectorHead = $this->makeSectorHead($sector);
        $dataAdmin = $this->makeDataAdmin($sector);
        $facilitator = $this->makeFacilitator($sector);

        // Submit and advance to facilitator review.
        Passport::actingAs($dataAdmin, [], 'api');
        $this->postJson("/api/v2/kpis/{$kpi->id}/submissions", ['quarter' => 'q1', 'actualValue' => '40', 'evidenceDocumentIds' => []])
            ->assertStatus(202);
        $tracking = PerformanceTracking::where('kpi_id', $kpi->id)->first();

        Passport::actingAs($sectorHead, [], 'api');
        $this->postJson("/api/v2/approvals/submissions/{$tracking->id}/review", ['role' => 'sector_head', 'decision' => 'accept'])
            ->assertStatus(202);

        // Facilitator rejects.
        Passport::actingAs($facilitator, [], 'api');
        $this->postJson("/api/v2/approvals/submissions/{$tracking->id}/review", [
            'role' => 'facilitator', 'decision' => 'reject', 'rejectionReason' => 'Evidence missing.',
        ])->assertStatus(202);

        $tracking->refresh();
        $this->assertSame('Rejected', $tracking->confirmation_status);
        $this->assertSame('Evidence missing.', $tracking->facilitator_rejection_reason);

        // Data admin can resubmit (workflow restarts).
        Passport::actingAs($dataAdmin, [], 'api');
        $this->postJson("/api/v2/kpis/{$kpi->id}/submissions", ['quarter' => 'q1', 'actualValue' => '70', 'evidenceDocumentIds' => [], 'remarks' => 'Evidence attached now.'])
            ->assertStatus(202);

        $tracking->refresh();
        $this->assertSame('Pending Sector Head Approval', $tracking->confirmation_status);
        $this->assertNull($tracking->facilitator_decision, 'Facilitator decision should be cleared on resubmit');
        $this->assertNull($tracking->facilitator_rejection_reason);
        $this->assertSame('70', $tracking->actual_value);
    }
}
