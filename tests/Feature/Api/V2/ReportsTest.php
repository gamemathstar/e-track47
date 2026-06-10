<?php

namespace Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPdcuAuth;

    private function seedSmallHierarchy(): array
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => 'Health']);
        $commitment = $this->makeCommitment($sector);
        $deliverable = $this->makeDeliverable($commitment);
        $kpi = $this->makeKpi($deliverable);
        $this->makeTracking($kpi, ['quarter' => 1, 'actual_value' => '80', 'milestone' => '100', 'confirmation_status' => 'Confirmed']);

        return [$fw, $sector, $kpi];
    }

    public function test_hub_returns_required_shape(): void
    {
        $this->seedSmallHierarchy();
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson('/api/v2/reports/hub')
            ->assertOk()
            ->assertJsonStructure([
                'avgPerformanceFraction', 'avgPerformanceLabel', 'topSectorLabel', 'pendingCount', 'pendingCaption',
                'sectorBars' => [['label', 'short', 'fraction', 'valueLabel', 'accent']],
                'statusMix' => ['achievedFraction', 'onTrackFraction', 'criticalFraction', 'totalKpiCount', 'achievedPctLabel', 'onTrackPctLabel', 'criticalPctLabel'],
            ])
            ->assertJsonPath('topSectorLabel', 'Health');
    }

    public function test_setup_preview_counts(): void
    {
        $this->seedSmallHierarchy();
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson('/api/v2/reports/setup-preview', [
            'sectorIds' => [], 'year' => 2024, 'quarter' => 'q1', 'includeEvidence' => true,
        ])->assertOk()
            ->assertJsonStructure(['commitmentsCount', 'deliverablesCount', 'kpisCount', 'fileSizeLabel'])
            ->assertJsonPath('commitmentsCount', 1)
            ->assertJsonPath('kpisCount', 1);
    }

    public function test_viewer_content_with_groups_and_rows(): void
    {
        $this->seedSmallHierarchy();
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson('/api/v2/reports/viewer', [
            'sectorIds' => [], 'year' => 2024, 'includeEvidence' => true,
        ])->assertOk()
            ->assertJsonStructure([
                'title', 'subtitle',
                'groups' => [['id', 'label', 'accent', 'kpiRows' => [['index', 'title', 'body', 'targetLabel', 'currentLabel', 'currentAccent', 'percentFraction', 'percentLabel', 'percentAccent']]]],
            ])
            ->assertJsonPath('groups.0.label', 'Health');
    }

    public function test_generate_comprehensive_returns_download_url(): void
    {
        Storage::fake('public');
        $this->seedSmallHierarchy();
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $res = $this->postJson('/api/v2/reports/comprehensive', [
            'sectorIds' => [], 'year' => 2024, 'quarter' => 'q1', 'includeEvidence' => true, 'format' => 'pdf',
        ])->assertOk()
            ->assertJsonStructure(['id', 'format', 'fileSizeLabel', 'downloadUrl'])
            ->assertJsonPath('format', 'pdf');

        $this->assertStringContainsString('uploads/reports/', $res->json('downloadUrl'));
    }

    public function test_generate_word(): void
    {
        Storage::fake('public');
        $this->seedSmallHierarchy();
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson('/api/v2/reports/word', [
            'sectorId' => '1', 'year' => 2024, 'quarter' => 'q1',
            'title' => 'Q1 review', 'author' => 'Coord', 'dateLabel' => 'March 2024',
        ])->assertOk()->assertJsonPath('format', 'word');
    }

    public function test_print_preview(): void
    {
        Passport::actingAs($this->makeUser(), [], 'api');

        $this->getJson('/api/v2/reports/print-preview')
            ->assertOk()
            ->assertJsonStructure(['pageCount', 'docNoLabel', 'docNoValue']);
    }

    public function test_setup_preview_validation(): void
    {
        Passport::actingAs($this->makeUser(), [], 'api');
        $this->postJson('/api/v2/reports/setup-preview', [])
            ->assertStatus(422)
            ->assertJsonStructure(['fieldErrors' => ['sectorIds', 'includeEvidence']]);
    }

    public function test_reports_require_auth(): void
    {
        $this->getJson('/api/v2/reports/hub')->assertStatus(401);
    }
}
