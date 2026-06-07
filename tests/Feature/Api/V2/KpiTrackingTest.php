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

    /**
     * Round-trip regression for the mobile contract bug — value fields are
     * opaque strings, must never be trailing-zero-stripped or float-cast.
     * Stored "120" must come back as "120", not "12"; "850" not "85".
     */
    public function test_milestone_round_trip_preserves_trailing_zeros(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        foreach (['120', '850', '1000', '8500.50'] as $value) {
            $this->postJson("/api/v2/kpis/{$kpi->id}/milestones", [
                'quarter' => 'q1', 'year' => 2024, 'value' => $value,
            ])->assertStatus(202);

            $this->getJson("/api/v2/kpis/{$kpi->id}/milestones?quarter=q1&year=2024")
                ->assertOk()
                ->assertExactJson(['value' => $value]);
        }
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

    public function test_tracking_context_returns_sheet_labels(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $body = $this->getJson("/api/v2/kpis/{$kpi->id}/tracking-context")
            ->assertOk()
            ->json();

        $this->assertSame((string) $kpi->id, $body['kpiId']);
        $this->assertSame('Clinics with EHR', $body['kpiTitle']);
        $this->assertSame('Maternal Health Expansion', $body['commitmentLabel']);
        $this->assertSame('%', $body['unit']);
        $this->assertSame(2024, $body['year']);
        $this->assertMatchesRegularExpression('/^q[1-4]$/', $body['quarter']);
    }

    public function test_tracking_context_omits_unit_and_milestone_when_absent(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw);
        $commitment = $this->makeCommitment($sector, ['name' => 'Maternal Health Expansion']);
        $deliverable = $this->makeDeliverable($commitment);
        $kpi = $this->makeKpi($deliverable, ['kpi' => 'Some KPI', 'unit_of_measurement' => '']);

        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $body = $this->getJson("/api/v2/kpis/{$kpi->id}/tracking-context")
            ->assertOk()
            ->json();

        $this->assertArrayNotHasKey('unit', $body);
        $this->assertArrayNotHasKey('currentMilestoneValue', $body);
    }

    public function test_tracking_context_includes_existing_milestone(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        // Seed a milestone for the quarter the endpoint is going to land on
        // (calendar quarter when no data-entry window is configured).
        $quarter = (int) ceil((int) date('n') / 3);
        $this->makeTracking($kpi, [
            'quarter' => $quarter, 'year' => 2024, 'milestone' => '85', 'actual_value' => null,
        ]);

        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $body = $this->getJson("/api/v2/kpis/{$kpi->id}/tracking-context")
            ->assertOk()
            ->json();

        $this->assertSame('85', $body['currentMilestoneValue']);
        $this->assertSame('q'.$quarter, $body['quarter']);
    }

    public function test_tracking_context_picks_open_window_quarter(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        // Lock the current calendar quarter, open Q3 explicitly. The endpoint
        // should default to Q3 instead of the calendar quarter.
        $calendar = (int) ceil((int) date('n') / 3);
        $other = $calendar === 3 ? 1 : 3;
        \DB::table('data_entry_accesses')->insert([
            'sector_id' => $sector->id, 'year' => 2024, 'quarter' => $calendar,
            'deadline_date' => now()->subDays(30)->toDateString(),
            'override_deadline' => null, 'status' => 'closed',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        \DB::table('data_entry_accesses')->insert([
            'sector_id' => $sector->id, 'year' => 2024, 'quarter' => $other,
            'deadline_date' => now()->addDays(20)->toDateString(),
            'override_deadline' => null, 'status' => 'open',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $body = $this->getJson("/api/v2/kpis/{$kpi->id}/tracking-context")
            ->assertOk()
            ->json();

        $this->assertSame('q'.$other, $body['quarter']);
    }

    public function test_tracking_context_404_for_unknown_kpi(): void
    {
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson('/api/v2/kpis/999999/tracking-context')
            ->assertStatus(404)->assertJsonPath('code', 'not_found');
    }

    public function test_tracking_context_requires_auth(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        $this->getJson("/api/v2/kpis/{$kpi->id}/tracking-context")
            ->assertStatus(401);
    }

    public function test_upload_evidence_returns_doc_id(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $body = $this->post("/api/v2/kpis/{$kpi->id}/evidence", [
            'file' => \Illuminate\Http\UploadedFile::fake()->image('evidence.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(201)
            ->json();

        $this->assertArrayHasKey('id', $body);
        $this->assertIsString($body['id']);

        $file = \App\Models\File::find((int) $body['id']);
        $this->assertNotNull($file);
        $this->assertNull($file->fileable_id);
        $this->assertStringStartsWith('uploads/evidence/', $file->path);
        // files.type must be the lowercase extension (matches the web upload's
        // convention so the web's preview blade renders an <img> rather than
        // falling through to "No preview").
        $this->assertSame('jpg', $file->type);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($file->path);
    }

    public function test_upload_evidence_rejects_non_image(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $this->post("/api/v2/kpis/{$kpi->id}/evidence", [
            'file' => \Illuminate\Http\UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonStructure(['fieldErrors' => ['file']]);
    }

    public function test_upload_evidence_rejects_oversize_file(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $this->post("/api/v2/kpis/{$kpi->id}/evidence", [
            // 6 MB image exceeds 5120 KB cap.
            'file' => \Illuminate\Http\UploadedFile::fake()->image('big.jpg')->size(6 * 1024),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonStructure(['fieldErrors' => ['file']]);
    }

    public function test_upload_evidence_forbidden_for_non_data_admin(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        // Sector Head can read the KPI but cannot enter data.
        Passport::actingAs($this->makeSectorHead($sector), [], 'api');

        $this->post("/api/v2/kpis/{$kpi->id}/evidence", [
            'file' => \Illuminate\Http\UploadedFile::fake()->image('evidence.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(403)->assertJsonPath('code', 'forbidden');
    }

    public function test_delete_evidence_removes_orphaned_file(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $docId = $this->post("/api/v2/kpis/{$kpi->id}/evidence", [
            'file' => \Illuminate\Http\UploadedFile::fake()->image('evidence.jpg'),
        ], ['Accept' => 'application/json'])->json('id');

        $path = \App\Models\File::find((int) $docId)->path;

        $this->delete("/api/v2/kpis/{$kpi->id}/evidence/{$docId}", [], ['Accept' => 'application/json'])
            ->assertStatus(204);

        $this->assertNull(\App\Models\File::find((int) $docId));
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($path);
    }

    public function test_delete_evidence_404_when_file_belongs_to_another_user(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        $uploader = $this->makeDataAdmin($sector);
        Passport::actingAs($uploader, [], 'api');

        $docId = $this->post("/api/v2/kpis/{$kpi->id}/evidence", [
            'file' => \Illuminate\Http\UploadedFile::fake()->image('evidence.jpg'),
        ], ['Accept' => 'application/json'])->json('id');

        // Different user tries to delete uploader's orphan.
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');
        $this->delete("/api/v2/kpis/{$kpi->id}/evidence/{$docId}", [], ['Accept' => 'application/json'])
            ->assertStatus(404)->assertJsonPath('code', 'not_found');

        $this->assertNotNull(\App\Models\File::find((int) $docId));
    }

    public function test_delete_evidence_404_when_already_attached(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        $admin = $this->makeDataAdmin($sector);
        Passport::actingAs($admin, [], 'api');

        $docId = $this->post("/api/v2/kpis/{$kpi->id}/evidence", [
            'file' => \Illuminate\Http\UploadedFile::fake()->image('evidence.jpg'),
        ], ['Accept' => 'application/json'])->json('id');

        // Submit a tracking entry that attaches this file.
        $this->postJson("/api/v2/kpis/{$kpi->id}/tracking-entries", [
            'quarter' => 'q1', 'year' => 2024, 'trackingDate' => '2024-09-15T00:00:00.000',
            'actualValue' => '62', 'evidenceDocumentIds' => [(string) $docId],
        ])->assertStatus(202);

        // Now the file is attached — delete must 404.
        $this->delete("/api/v2/kpis/{$kpi->id}/evidence/{$docId}", [], ['Accept' => 'application/json'])
            ->assertStatus(404)->assertJsonPath('code', 'not_found');
    }

    public function test_attached_files_are_renamed_to_evidence_n_per_quarter(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        $admin = $this->makeDataAdmin($sector);
        Passport::actingAs($admin, [], 'api');

        // Upload three files (orphans for now — original filenames preserved
        // until they bind to a tracking row).
        $ids = [];
        foreach (['raw_one.jpg', 'raw_two.png', 'raw_three.jpg'] as $name) {
            $ids[] = $this->post("/api/v2/kpis/{$kpi->id}/evidence", [
                'file' => \Illuminate\Http\UploadedFile::fake()->image($name),
            ], ['Accept' => 'application/json'])->json('id');
        }

        // Submit Q1 with all three — they become Attachment 1/2/3 in id order.
        $this->postJson("/api/v2/kpis/{$kpi->id}/tracking-entries", [
            'quarter' => 'q1', 'year' => 2024,
            'trackingDate' => '2024-04-10T00:00:00.000',
            'actualValue' => '50',
            'evidenceDocumentIds' => array_map('strval', $ids),
        ])->assertStatus(202);

        $q1Files = \App\Models\File::whereIn('id', $ids)->orderBy('id')->get();
        $this->assertSame('Evidence 1', $q1Files[0]->name);
        $this->assertSame('Evidence 2', $q1Files[1]->name);
        $this->assertSame('Evidence 3', $q1Files[2]->name);

        // Upload + submit a NEW file against Q2 → indexing resets to 1 (Q2's
        // tracking row has zero existing attachments).
        $q2DocId = $this->post("/api/v2/kpis/{$kpi->id}/evidence", [
            'file' => \Illuminate\Http\UploadedFile::fake()->image('next_quarter.jpg'),
        ], ['Accept' => 'application/json'])->json('id');

        $this->postJson("/api/v2/kpis/{$kpi->id}/tracking-entries", [
            'quarter' => 'q2', 'year' => 2024,
            'trackingDate' => '2024-07-10T00:00:00.000',
            'actualValue' => '60',
            'evidenceDocumentIds' => [(string) $q2DocId],
        ])->assertStatus(202);

        $this->assertSame('Evidence 1', \App\Models\File::find((int) $q2DocId)->name);
    }

    public function test_evidence_renumbering_continues_on_resubmit_within_same_quarter(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        $admin = $this->makeDataAdmin($sector);
        Passport::actingAs($admin, [], 'api');

        // First batch — two files.
        $first = [];
        foreach (['a.jpg', 'b.jpg'] as $name) {
            $first[] = $this->post("/api/v2/kpis/{$kpi->id}/evidence", [
                'file' => \Illuminate\Http\UploadedFile::fake()->image($name),
            ], ['Accept' => 'application/json'])->json('id');
        }
        $this->postJson("/api/v2/kpis/{$kpi->id}/tracking-entries", [
            'quarter' => 'q3', 'year' => 2024,
            'trackingDate' => '2024-09-01T00:00:00.000',
            'actualValue' => '40',
            'evidenceDocumentIds' => array_map('strval', $first),
        ])->assertStatus(202);

        // Second batch on the same quarter — should continue at 3, not reset.
        $secondDocId = $this->post("/api/v2/kpis/{$kpi->id}/evidence", [
            'file' => \Illuminate\Http\UploadedFile::fake()->image('c.jpg'),
        ], ['Accept' => 'application/json'])->json('id');

        $this->postJson("/api/v2/kpis/{$kpi->id}/tracking-entries", [
            'quarter' => 'q3', 'year' => 2024,
            'trackingDate' => '2024-09-02T00:00:00.000',
            'actualValue' => '45',
            'evidenceDocumentIds' => [(string) $secondDocId],
        ])->assertStatus(202);

        // Existing attachments keep their names; new one continues the sequence.
        $names = \App\Models\File::whereIn('id', array_merge($first, [$secondDocId]))
            ->orderBy('id')->pluck('name')->all();
        $this->assertSame(['Evidence 1', 'Evidence 2', 'Evidence 3'], $names);
    }

    public function test_submit_only_attaches_files_uploaded_by_caller(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        $admin = $this->makeDataAdmin($sector);
        $coordinator = $this->makeUser([], 'Coordinator');

        // Coordinator uploads a file for the same KPI.
        Passport::actingAs($coordinator, [], 'api');
        $stolenDocId = $this->post("/api/v2/kpis/{$kpi->id}/evidence", [
            'file' => \Illuminate\Http\UploadedFile::fake()->image('coord.jpg'),
        ], ['Accept' => 'application/json'])->json('id');

        // Data admin submits, including the coordinator's file id in the body.
        Passport::actingAs($admin, [], 'api');
        $ownDocId = $this->post("/api/v2/kpis/{$kpi->id}/evidence", [
            'file' => \Illuminate\Http\UploadedFile::fake()->image('own.jpg'),
        ], ['Accept' => 'application/json'])->json('id');

        $this->postJson("/api/v2/kpis/{$kpi->id}/tracking-entries", [
            'quarter' => 'q1', 'year' => 2024, 'trackingDate' => '2024-09-15T00:00:00.000',
            'actualValue' => '62', 'evidenceDocumentIds' => [(string) $stolenDocId, (string) $ownDocId],
        ])->assertStatus(202);

        // The data admin's own upload got attached; the coordinator's orphan did NOT.
        $this->assertNotNull(\App\Models\File::find((int) $ownDocId)->fileable_id);
        $this->assertNull(\App\Models\File::find((int) $stolenDocId)->fileable_id);
    }

    public function test_upload_evidence_requires_auth(): void
    {
        [$sector, $deliverable, $kpi] = $this->seedKpi();
        $this->post("/api/v2/kpis/{$kpi->id}/evidence", [], ['Accept' => 'application/json'])
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
