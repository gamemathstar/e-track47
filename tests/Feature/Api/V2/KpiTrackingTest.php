<?php

namespace Tests\Feature\Api\V2;

use App\Models\PerformanceTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

/**
 * §11.4 KPI tracking: list/detail read shapes + the three queued command
 * endpoints, with workflow, locking, validation and role authorization.
 */
class KpiTrackingTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPdcuAuth;

    /** @return array{0:\App\Models\Sector,1:\App\Models\Deliverable,2:\App\Models\Kpi} */
    private function seedKpi(): array
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw);
        $commitment = $this->makeCommitment($sector, ['name' => 'Maternal Health Expansion']);
        $deliverable = $this->makeDeliverable($commitment);
        $kpi = $this->makeKpi($deliverable, ['kpi' => 'Clinics with EHR', 'unit_of_measurement' => '%']);
        $this->makeKpiTarget($kpi, 85, 2024);

        return [$sector, $deliverable, $kpi];
    }

    public function test_list_kpis_for_deliverable(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        $this->makeTracking($kpi, ['quarter' => 1, 'actual_value' => '80', 'milestone' => '100', 'confirmation_status' => 'Confirmed']);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson("/api/v2/deliverables/{$deliverable->id}/kpis")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonStructure([['id', 'deliverableId', 'title', 'targetLabel', 'statusLabel', 'status', 'quartersOverview', 'lastUpdatedLabel']])
            ->assertJsonPath('0.title', 'Clinics with EHR')
            ->assertJsonPath('0.targetLabel', 'Target: 85%')
            ->assertJsonPath('0.quartersOverview.0', 'completed')
            ->assertJsonPath('0.quartersOverview.1', 'pending');
    }

    public function test_kpi_detail_includes_submissions_and_docs(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        $this->makeTracking($kpi, ['quarter' => 1, 'actual_value' => '84', 'milestone' => '100', 'confirmation_status' => 'Confirmed', 'remarks' => 'Q1 done']);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson("/api/v2/kpis/{$kpi->id}")
            ->assertOk()
            ->assertJsonPath('id', (string) $kpi->id)
            ->assertJsonPath('year', 2024)
            ->assertJsonPath('parentCommitmentTitle', 'Maternal Health Expansion')
            ->assertJsonPath('submissions.0.quarter', 'q1')
            ->assertJsonPath('submissions.0.status', 'confirmed')
            ->assertJsonPath('submissions.0.actual', '84')
            ->assertJsonStructure(['submissions', 'supportingDocuments', 'activeQuarter', 'progressPercent']);
    }

    public function test_data_admin_can_submit_performance(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        Passport::actingAs($this->makeUser(['target_entity' => 'Sector', 'entity_id' => $sector->id], 'Data Admin'), [], 'api');

        $this->postJson("/api/v2/kpis/{$kpi->id}/submissions", [
            'quarter' => 'q1',
            'actualValue' => '84',
            'evidenceDocumentIds' => [],
            'remarks' => 'Lagos hubs migrated.',
        ])->assertStatus(202);

        $row = PerformanceTracking::where('kpi_id', $kpi->id)->where('quarter', 1)->first();
        $this->assertNotNull($row);
        $this->assertSame('84', $row->actual_value);
        $this->assertSame('Pending Sector Head Approval', $row->confirmation_status);
    }

    public function test_submit_is_forbidden_for_non_data_admin(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        // Sector head can READ the sector but is not the Data Admin → 403 on entry.
        Passport::actingAs($this->makeSectorHead($sector), [], 'api');

        $this->postJson("/api/v2/kpis/{$kpi->id}/submissions", ['quarter' => 'q1', 'actualValue' => '10', 'evidenceDocumentIds' => []])
            ->assertStatus(403)
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_submit_validation_errors(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        Passport::actingAs($this->makeUser(['target_entity' => 'Sector', 'entity_id' => $sector->id], 'Data Admin'), [], 'api');

        $this->postJson("/api/v2/kpis/{$kpi->id}/submissions", [])
            ->assertStatus(422)
            ->assertJsonPath('code', 'validation_error')
            ->assertJsonStructure(['fieldErrors' => ['quarter', 'actualValue']]);
    }

    public function test_submit_conflicts_when_quarter_confirmed(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        $this->makeTracking($kpi, ['quarter' => 1, 'actual_value' => '80', 'confirmation_status' => 'Confirmed']);
        Passport::actingAs($this->makeUser(['target_entity' => 'Sector', 'entity_id' => $sector->id], 'Data Admin'), [], 'api');

        $this->postJson("/api/v2/kpis/{$kpi->id}/submissions", ['quarter' => 'q1', 'actualValue' => '90', 'evidenceDocumentIds' => []])
            ->assertStatus(409)
            ->assertJsonPath('code', 'conflict');
    }

    public function test_coordinator_can_set_milestone(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson("/api/v2/kpis/{$kpi->id}/milestones", ['quarter' => 'q2', 'year' => 2024, 'value' => '90'])
            ->assertStatus(202);

        $this->assertSame('90', PerformanceTracking::where('kpi_id', $kpi->id)->where('quarter', 2)->first()->milestone);
    }

    public function test_get_milestone_returns_saved_value(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        $this->makeTracking($kpi, ['quarter' => 1, 'year' => 2024, 'milestone' => '85', 'actual_value' => null]);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson("/api/v2/kpis/{$kpi->id}/milestones?quarter=q1&year=2024")
            ->assertOk()
            ->assertExactJson(['value' => '85']);
    }

    public function test_get_milestone_returns_null_when_unset(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        // No tracking row exists for Q3/2024.
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson("/api/v2/kpis/{$kpi->id}/milestones?quarter=q3&year=2024")
            ->assertOk()
            ->assertExactJson(['value' => null]);
    }

    public function test_get_milestone_returns_null_when_row_exists_without_milestone(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        // Row exists for Q2 with actual_value but no milestone yet.
        $this->makeTracking($kpi, ['quarter' => 2, 'year' => 2024, 'milestone' => null, 'actual_value' => '40']);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson("/api/v2/kpis/{$kpi->id}/milestones?quarter=q2&year=2024")
            ->assertOk()
            ->assertExactJson(['value' => null]);
    }

    public function test_get_milestone_requires_quarter_and_year(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson("/api/v2/kpis/{$kpi->id}/milestones")
            ->assertStatus(422)
            ->assertJsonStructure(['fieldErrors' => ['quarter', 'year']]);
    }

    public function test_get_milestone_404_for_unknown_kpi(): void
    {
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson('/api/v2/kpis/999999/milestones?quarter=q1&year=2024')
            ->assertStatus(404)->assertJsonPath('code', 'not_found');
    }

    public function test_get_milestone_requires_auth(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        $this->getJson("/api/v2/kpis/{$kpi->id}/milestones?quarter=q1&year=2024")
            ->assertStatus(401);
    }

    public function test_data_admin_can_add_tracking_entry(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        Passport::actingAs($this->makeUser(['target_entity' => 'Sector', 'entity_id' => $sector->id], 'Data Admin'), [], 'api');

        $this->postJson("/api/v2/kpis/{$kpi->id}/tracking-entries", [
            'quarter' => 'q1', 'year' => 2024, 'trackingDate' => '2024-09-15T00:00:00.000', 'actualValue' => '62', 'evidenceDocumentIds' => [],
        ])->assertStatus(202);

        $this->assertSame('62', PerformanceTracking::where('kpi_id', $kpi->id)->where('quarter', 1)->first()->actual_value);
    }

    public function test_kpi_read_requires_auth(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        $this->getJson("/api/v2/kpis/{$kpi->id}")->assertStatus(401)->assertJsonPath('code', 'unauthenticated');
    }

    public function test_cross_sector_kpi_is_404(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        $otherSector = $this->makeSector($sector->framework, ['sector_name' => 'Education']);

        Passport::actingAs($this->makeSectorHead($otherSector), [], 'api');

        $this->getJson("/api/v2/kpis/{$kpi->id}")->assertStatus(404)->assertJsonPath('code', 'not_found');
    }
}
