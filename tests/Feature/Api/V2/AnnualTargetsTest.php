<?php

namespace Tests\Feature\Api\V2;

use App\Models\KpiTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

/**
 * §11.4.6 annual KPI targets per deliverable: list + bulk save.
 */
class AnnualTargetsTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPdcuAuth;

    /** @return array{sector: \App\Models\Sector, commitment: \App\Models\Commitment, deliverable: \App\Models\Deliverable, kpis: array<int, \App\Models\Kpi>} */
    private function seedDeliverable(): array
    {
        $fw = $this->makeFramework(['year' => 2024]);
        $sector = $this->makeSector($fw, ['sector_name' => 'Health']);
        $commitment = $this->makeCommitment($sector, ['name' => 'Maternal Health Expansion']);
        $deliverable = $this->makeDeliverable($commitment, ['deliverable' => 'Clinic digitization']);
        $kpi1 = $this->makeKpi($deliverable, ['kpi' => 'Clinics with EHR', 'unit_of_measurement' => '%']);
        $kpi2 = $this->makeKpi($deliverable, ['kpi' => 'LGAs benefiting', 'unit_of_measurement' => 'LGAs']);

        return compact('sector', 'commitment', 'deliverable') + ['kpis' => [$kpi1, $kpi2]];
    }

    public function test_list_returns_one_row_per_kpi_with_baseline_and_category(): void
    {
        ['deliverable' => $d, 'kpis' => $kpis] = $this->seedDeliverable();
        // Baseline source: latest confirmed actual_value per KPI.
        $this->makeTracking($kpis[0], ['quarter' => 1, 'year' => 2023, 'actual_value' => '40', 'milestone' => '100', 'confirmation_status' => 'Confirmed']);
        $this->makeTracking($kpis[0], ['quarter' => 3, 'year' => 2023, 'actual_value' => '48', 'milestone' => '100', 'confirmation_status' => 'Confirmed']); // most recent confirmed
        // KPI 2 has nothing confirmed → baseline falls back to "0".

        // One KPI already has a target for 2024; the other doesn't.
        $t = new KpiTarget();
        $t->kpi_id = $kpis[0]->id;
        $t->year = 2024;
        $t->target = '120';
        $t->save();

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $body = $this->getJson("/api/v2/deliverables/{$d->id}/annual-targets?year=2024")
            ->assertOk()
            ->assertJsonCount(2)
            ->json();

        $this->assertSame((string) $kpis[0]->id, $body[0]['kpiId']);
        $this->assertSame('Maternal Health Expansion', $body[0]['category']);
        $this->assertSame('Clinics with EHR', $body[0]['title']);
        $this->assertSame('48', $body[0]['baselineValue']);
        $this->assertSame('%', $body[0]['baselineUnit']);
        $this->assertSame('%', $body[0]['targetUnit']);
        $this->assertSame('120', $body[0]['targetValue']);

        // Second KPI has no target row → targetValue key absent. No confirmed
        // submissions either → baseline = "0".
        $this->assertArrayNotHasKey('targetValue', $body[1]);
        $this->assertSame('0', $body[1]['baselineValue']);
    }

    public function test_list_requires_year_param(): void
    {
        ['deliverable' => $d] = $this->seedDeliverable();
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson("/api/v2/deliverables/{$d->id}/annual-targets")
            ->assertStatus(422)
            ->assertJsonStructure(['fieldErrors' => ['year']]);
    }

    public function test_list_404_on_unknown_deliverable(): void
    {
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');
        $this->getJson('/api/v2/deliverables/999999/annual-targets?year=2024')
            ->assertStatus(404)->assertJsonPath('code', 'not_found');
    }

    public function test_save_upserts_targets_and_leaves_omitted_kpis_untouched(): void
    {
        ['deliverable' => $d, 'kpis' => $kpis] = $this->seedDeliverable();
        // Pre-existing target for kpi[1] = 50; payload won't touch it.
        $pre = new KpiTarget();
        $pre->kpi_id = $kpis[1]->id;
        $pre->year = 2024;
        $pre->target = '50';
        $pre->save();

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson("/api/v2/deliverables/{$d->id}/annual-targets", [
            'year' => 2024,
            'targets' => [
                ['kpiId' => (string) $kpis[0]->id, 'value' => '120'],
            ],
        ])->assertStatus(202);

        // Verbatim string storage now — no DECIMAL→".00" inflation.
        $this->assertSame('120', (string) KpiTarget::where(['kpi_id' => $kpis[0]->id, 'year' => 2024])->first()->target);
        // The omitted KPI is unchanged.
        $this->assertSame('50', (string) KpiTarget::where(['kpi_id' => $kpis[1]->id, 'year' => 2024])->first()->target);
    }

    public function test_save_is_atomic_when_one_kpi_does_not_belong_to_the_deliverable(): void
    {
        ['deliverable' => $d, 'kpis' => $kpis] = $this->seedDeliverable();
        // Build an unrelated KPI under a different deliverable.
        $other = $this->makeKpi($this->makeDeliverable($this->makeCommitment($this->makeSector($this->makeFramework()))));

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson("/api/v2/deliverables/{$d->id}/annual-targets", [
            'year' => 2024,
            'targets' => [
                ['kpiId' => (string) $kpis[0]->id, 'value' => '120'],
                ['kpiId' => (string) $other->id, 'value' => '999'],
            ],
        ])->assertStatus(422)
            ->assertJsonStructure(['fieldErrors' => ['targets.1.kpiId']]);

        // The valid one shouldn't have landed either — atomic.
        $this->assertNull(KpiTarget::where(['kpi_id' => $kpis[0]->id, 'year' => 2024])->first());
    }

    public function test_save_rejects_negative_and_non_numeric_values(): void
    {
        ['deliverable' => $d, 'kpis' => $kpis] = $this->seedDeliverable();
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson("/api/v2/deliverables/{$d->id}/annual-targets", [
            'year' => 2024,
            'targets' => [
                ['kpiId' => (string) $kpis[0]->id, 'value' => '-10'],
                ['kpiId' => (string) $kpis[1]->id, 'value' => 'abc'],
            ],
        ])->assertStatus(422)
            ->assertJsonStructure(['fieldErrors' => ['targets.0.value', 'targets.1.value']]);
    }

    public function test_save_requires_year_and_at_least_one_target(): void
    {
        ['deliverable' => $d] = $this->seedDeliverable();
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson("/api/v2/deliverables/{$d->id}/annual-targets", [])
            ->assertStatus(422)
            ->assertJsonStructure(['fieldErrors' => ['year', 'targets']]);
    }

    public function test_save_forbidden_for_non_pdcu_user(): void
    {
        ['deliverable' => $d, 'sector' => $s, 'kpis' => $kpis] = $this->seedDeliverable();
        // Data Admin is NOT in isDeliveryUnit; should be 403.
        Passport::actingAs($this->makeDataAdmin($s), [], 'api');

        $this->postJson("/api/v2/deliverables/{$d->id}/annual-targets", [
            'year' => 2024,
            'targets' => [['kpiId' => (string) $kpis[0]->id, 'value' => '120']],
        ])->assertStatus(403)->assertJsonPath('code', 'forbidden');
    }

    public function test_facilitator_can_save_for_assigned_sector_only(): void
    {
        ['deliverable' => $d, 'sector' => $s, 'kpis' => $kpis] = $this->seedDeliverable();
        // Facilitator assigned to the sector — should be allowed.
        Passport::actingAs($this->makeFacilitator($s), [], 'api');

        $this->postJson("/api/v2/deliverables/{$d->id}/annual-targets", [
            'year' => 2024,
            'targets' => [['kpiId' => (string) $kpis[0]->id, 'value' => '120']],
        ])->assertStatus(202);
    }

    public function test_facilitator_for_other_sector_gets_404(): void
    {
        ['deliverable' => $d] = $this->seedDeliverable();
        $other = $this->makeSector($this->makeFramework(), ['sector_name' => 'Education']);
        Passport::actingAs($this->makeFacilitator($other), [], 'api');

        $this->getJson("/api/v2/deliverables/{$d->id}/annual-targets?year=2024")
            ->assertStatus(404)->assertJsonPath('code', 'not_found');
    }

    public function test_requires_authentication(): void
    {
        ['deliverable' => $d] = $this->seedDeliverable();

        $this->getJson("/api/v2/deliverables/{$d->id}/annual-targets?year=2024")
            ->assertStatus(401);
        $this->postJson("/api/v2/deliverables/{$d->id}/annual-targets", [
            'year' => 2024, 'targets' => [['kpiId' => '1', 'value' => '1']],
        ])->assertStatus(401);
    }
}
