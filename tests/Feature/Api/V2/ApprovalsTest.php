<?php

namespace Tests\Feature\Api\V2;

use App\Models\PerformanceTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

/**
 * §11.6 approvals workflow: the role queues, submission detail, the review
 * transitions across the §4 state machine, bulk-approve, and authorization.
 */
class ApprovalsTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPdcuAuth;

    /** Seed a KPI under a sector and return [sector, kpi]. */
    private function seedKpi(string $sectorName = 'Health'): array
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => $sectorName, 'ministry' => 'Ministry of '.$sectorName]);
        $commitment = $this->makeCommitment($sector, ['name' => 'Maternal Health Expansion']);
        $deliverable = $this->makeDeliverable($commitment);
        $kpi = $this->makeKpi($deliverable, ['kpi' => 'Clinics with EHR', 'unit_of_measurement' => '%']);
        $this->makeKpiTarget($kpi, 85, 2024);

        return [$sector, $kpi];
    }

    /** Real user we can reference from the WHO columns (FK to users.id). */
    private ?int $workflowActorId = null;

    private function workflowActorId(): int
    {
        return $this->workflowActorId ??= $this->makeUser([], 'Coordinator')->id;
    }

    private function tracking($kpi, string $state, int $quarter = 1, array $attrs = []): PerformanceTracking
    {
        // Mirror the workflow's WHO columns for each state so the v2 services
        // (which now derive "awaiting facilitator" from those columns) see a
        // realistic row. Without this, a row with confirmation_status =
        // 'Pending Facilitator' but no sector_head_approved_by would be
        // invisible to ApprovalService::applyFacilitatorAwaitingScope.
        $actor = $this->workflowActorId();
        $whoColumns = match ($state) {
            'Pending Facilitator' => ['sector_head_approved_by' => $actor, 'sector_head_approved_at' => now()],
            'Pending Coordinator' => [
                'sector_head_approved_by' => $actor, 'sector_head_approved_at' => now(),
                'facilitator_confirmed_by' => $actor, 'facilitator_confirmed_at' => now(),
                'facilitator_decision' => 'Accept',
            ],
            'Confirmed' => [
                'sector_head_approved_by' => $actor, 'sector_head_approved_at' => now(),
                'facilitator_confirmed_by' => $actor, 'facilitator_confirmed_at' => now(),
                'facilitator_decision' => 'Accept',
                'coordinator_confirmed_by' => $actor, 'coordinator_confirmed_at' => now(),
            ],
            default => [],
        };

        return $this->makeTracking($kpi, array_merge([
            'quarter' => $quarter, 'actual_value' => '80', 'milestone' => '100', 'confirmation_status' => $state,
        ], $whoColumns, $attrs));
    }

    public function test_coordinator_queue_lists_pending_coordinator(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $t = $this->tracking($kpi, 'Pending Coordinator');

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson('/api/v2/approvals/coordinator/queue')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonStructure([['id', 'kpiId', 'kpiTitle', 'sectorLabel', 'sectorAccent', 'state']])
            ->assertJsonPath('0.id', (string) $t->id)
            ->assertJsonPath('0.kpiId', (string) $kpi->id)
            ->assertJsonPath('0.state', 'pending_coordinator');
    }

    public function test_sector_head_queue_and_bulk_grouping(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $this->tracking($kpi, 'Pending Sector Head Approval', 1);

        Passport::actingAs($this->makeSectorHead($sector), [], 'api');

        $this->getJson('/api/v2/approvals/sector-head/queue')
            ->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.state', 'pending_sector_head');

        $this->getJson('/api/v2/approvals/sector-head/bulk?grouping=by_commitment')
            ->assertOk()
            ->assertJsonStructure([
                'quarterLabel',
                'sectorLabel',
                'groups' => [['title', 'items' => [['id', 'title', 'value', 'adminName']]]],
            ])
            ->assertJsonPath('sectorLabel', 'Health')
            ->assertJsonPath('groups.0.title', 'Commitment: Maternal Health Expansion');
    }

    public function test_sector_head_bulk_response_wraps_groups_with_header_metadata(): void
    {
        [$sector, $kpi] = $this->seedKpi('Health');
        $this->tracking($kpi, 'Pending Sector Head Approval', 1);
        Passport::actingAs($this->makeSectorHead($sector), [], 'api');

        $body = $this->getJson('/api/v2/approvals/sector-head/bulk?grouping=by_commitment')
            ->assertOk()->json();

        // Wrapper shape: quarterLabel + sectorLabel + groups
        $this->assertArrayHasKey('quarterLabel', $body);
        $this->assertArrayHasKey('sectorLabel', $body);
        $this->assertArrayHasKey('groups', $body);

        // Sector label is the raw sector name — the client adds the "Sector: " prefix.
        $this->assertSame('Health', $body['sectorLabel']);
        // Quarter label is "{framework_year} Q{current_calendar_quarter}".
        $expected = '2024 Q'.((int) ceil((int) date('n') / 3));
        $this->assertSame($expected, $body['quarterLabel']);

        // Items still surface inside groups[].
        $this->assertNotEmpty($body['groups']);
        $this->assertSame('Commitment: Maternal Health Expansion', $body['groups'][0]['title']);
        $this->assertNotEmpty($body['groups'][0]['items']);
    }

    public function test_sector_head_bulk_requires_grouping(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        Passport::actingAs($this->makeSectorHead($sector), [], 'api');

        $this->getJson('/api/v2/approvals/sector-head/bulk')
            ->assertStatus(422)
            ->assertJsonPath('code', 'validation_error');
    }

    public function test_facilitator_queue_grouped_by_sector(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $this->tracking($kpi, 'Pending Facilitator');

        Passport::actingAs($this->makeFacilitator($sector), [], 'api');

        $this->getJson('/api/v2/approvals/facilitator/queue?grouping=by_sector')
            ->assertOk()
            ->assertJsonStructure([['id', 'title', 'accent', 'items' => [['id', 'kpiId', 'state', 'quarter']]]])
            ->assertJsonPath('0.items.0.state', 'pending_facilitator')
            ->assertJsonPath('0.items.0.quarter', 'q1');
    }

    public function test_facilitator_queue_narrows_to_requested_quarter(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $this->tracking($kpi, 'Pending Facilitator', 1);
        $this->tracking($kpi, 'Pending Facilitator', 3);

        Passport::actingAs($this->makeFacilitator($sector), [], 'api');

        // No filter → both quarters present.
        $all = $this->getJson('/api/v2/approvals/facilitator/queue?grouping=by_sector')
            ->assertOk()->json();
        $this->assertCount(2, $all[0]['items']);

        // ?quarter=q3 → only the Q3 row.
        $q3 = $this->getJson('/api/v2/approvals/facilitator/queue?grouping=by_sector&quarter=q3')
            ->assertOk()->json();
        $this->assertCount(1, $q3[0]['items']);
        $this->assertSame('q3', $q3[0]['items'][0]['quarter']);
    }

    public function test_sector_head_queue_includes_rows_with_stale_not_confirmed_status(): void
    {
        // Production scenario: data admin submitted (actual_value set) but
        // confirmation_status is stuck at 'Not Confirmed' instead of moving
        // to 'Pending Sector Head Approval'. The SH queue must still surface
        // these — they're genuinely waiting on the sector head.
        [$sector, $kpi] = $this->seedKpi();
        $tracking = $this->makeTracking($kpi, [
            'quarter' => 1, 'actual_value' => '80', 'milestone' => '100',
            // WHO columns all null — SH hasn't acted, no one downstream either.
            'sector_head_approved_by' => null,
            'facilitator_confirmed_by' => null,
            'coordinator_confirmed_by' => null,
            // Stale status — should be 'Pending Sector Head Approval'.
            'confirmation_status' => 'Not Confirmed',
        ]);

        Passport::actingAs($this->makeSectorHead($sector), [], 'api');

        $this->getJson('/api/v2/approvals/sector-head/queue')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', (string) $tracking->id);

        // Dashboard pendingApprovals agrees with the queue count.
        $this->getJson('/api/v2/dashboard/sector-head')
            ->assertOk()
            ->assertJsonPath('pendingApprovals', 1);
    }

    public function test_sector_head_queue_excludes_pre_submission_rows(): void
    {
        // Rows where the data admin hasn't entered actual_value yet are NOT
        // awaiting sector head — they're awaiting the data admin.
        [$sector, $kpi] = $this->seedKpi();
        $this->makeTracking($kpi, [
            'quarter' => 1, 'milestone' => '100',
            'actual_value' => null,
            'confirmation_status' => 'Not Confirmed',
        ]);

        Passport::actingAs($this->makeSectorHead($sector), [], 'api');

        $this->getJson('/api/v2/approvals/sector-head/queue')
            ->assertOk()->assertJsonCount(0);
    }

    public function test_coordinator_queue_shows_rows_when_confirmation_status_is_stale(): void
    {
        // Production scenario: facilitator accepted via a flow that set the
        // WHO columns but left confirmation_status stale (e.g. 'Pending
        // Facilitator'). The coordinator queue must still surface the row.
        [$sector, $kpi] = $this->seedKpi();
        $sectorHead = $this->makeSectorHead($sector);
        $facilitator = $this->makeFacilitator($sector);

        $tracking = $this->makeTracking($kpi, [
            'quarter' => 1, 'actual_value' => '49', 'milestone' => '100',
            'sector_head_approved_by' => $sectorHead->id,
            'sector_head_approved_at' => now(),
            'facilitator_confirmed_by' => $facilitator->id,
            'facilitator_confirmed_at' => now(),
            'facilitator_decision' => 'Accept',
            'delivery_department_value' => '49',
            // Stale — should be 'Pending Coordinator' but isn't.
            'confirmation_status' => 'Pending Facilitator',
        ]);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson('/api/v2/approvals/coordinator/queue')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', (string) $tracking->id);

        // Dashboard reviewQueueCount picks it up too.
        $this->getJson('/api/v2/dashboard/coordinator')
            ->assertOk()
            ->assertJsonPath('reviewQueueCount', 1);
    }

    public function test_coordinator_queue_filters_by_sector_year_quarter_and_sort(): void
    {
        // Build two sectors and two pending-coordinator rows per sector, each at
        // a different year/quarter, so we can exercise every filter axis.
        $fw = $this->makeFramework(['year' => 2024]);
        $health = $this->makeSector($fw, ['sector_name' => 'Health']);
        $agri = $this->makeSector($fw, ['sector_name' => 'Agriculture']);

        $kpiHealth = $this->makeKpi($this->makeDeliverable($this->makeCommitment($health)));
        $kpiAgri   = $this->makeKpi($this->makeDeliverable($this->makeCommitment($agri)));

        $sh = $this->makeSectorHead($health);
        $fc = $this->makeFacilitator($health);

        // Health rows: 2024-Q1 (older) and 2024-Q3 (newer).
        // PerformanceTracking::create overwrites updated_at with now(); set
        // it explicitly afterward so the sort axis is testable.
        $hQ1 = $this->makeTracking($kpiHealth, [
            'year' => 2024, 'quarter' => 1, 'actual_value' => '10', 'milestone' => '100',
            'sector_head_approved_by' => $sh->id, 'sector_head_approved_at' => now()->subDays(20),
            'facilitator_confirmed_by' => $fc->id, 'facilitator_confirmed_at' => now()->subDays(15),
            'facilitator_decision' => 'Accept',
        ]);
        \DB::table('performance_trackings')->where('id', $hQ1->id)->update(['updated_at' => now()->subDays(10)]);

        $hQ3 = $this->makeTracking($kpiHealth, [
            'year' => 2024, 'quarter' => 3, 'actual_value' => '30', 'milestone' => '100',
            'sector_head_approved_by' => $sh->id, 'sector_head_approved_at' => now()->subDays(5),
            'facilitator_confirmed_by' => $fc->id, 'facilitator_confirmed_at' => now()->subDays(3),
            'facilitator_decision' => 'Accept',
        ]);
        \DB::table('performance_trackings')->where('id', $hQ3->id)->update(['updated_at' => now()->subDays(1)]);

        // Agriculture row: 2024-Q3, newest.
        $aQ3 = $this->makeTracking($kpiAgri, [
            'year' => 2024, 'quarter' => 3, 'actual_value' => '40', 'milestone' => '100',
            'sector_head_approved_by' => $sh->id, 'sector_head_approved_at' => now()->subDays(2),
            'facilitator_confirmed_by' => $fc->id, 'facilitator_confirmed_at' => now()->subDays(2),
            'facilitator_decision' => 'Accept',
        ]);
        \DB::table('performance_trackings')->where('id', $aQ3->id)->update(['updated_at' => now()->subHours(6)]);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        // No filters → all 3, newest first by default.
        $all = $this->getJson('/api/v2/approvals/coordinator/queue')->assertOk()->json();
        $this->assertCount(3, $all);
        $this->assertSame((string) $aQ3->id, $all[0]['id']); // most recent updated_at

        // sort=oldest → reversed order, oldest first.
        $oldest = $this->getJson('/api/v2/approvals/coordinator/queue?sort=oldest')->assertOk()->json();
        $this->assertSame((string) $hQ1->id, $oldest[0]['id']);

        // sector filter → only Health rows (both quarters).
        $bySector = $this->getJson("/api/v2/approvals/coordinator/queue?sector={$health->id}")
            ->assertOk()->json();
        $this->assertCount(2, $bySector);

        // quarter filter → only Q3 rows (Health + Agri).
        $byQ = $this->getJson('/api/v2/approvals/coordinator/queue?quarter=q3')
            ->assertOk()->json();
        $this->assertCount(2, $byQ);

        // year filter (2024) → all three; year=2023 → none.
        $this->assertCount(3, $this->getJson('/api/v2/approvals/coordinator/queue?year=2024')->assertOk()->json());
        $this->assertCount(0, $this->getJson('/api/v2/approvals/coordinator/queue?year=2023')->assertOk()->json());

        // Combined: sector=Health & quarter=q3 → exactly hQ3.
        $combined = $this->getJson("/api/v2/approvals/coordinator/queue?sector={$health->id}&quarter=q3&year=2024&sort=newest")
            ->assertOk()->json();
        $this->assertCount(1, $combined);
        $this->assertSame((string) $hQ3->id, $combined[0]['id']);
    }

    public function test_coordinator_queue_excludes_facilitator_rejected_rows(): void
    {
        // A facilitator REJECTION must not appear in the coordinator queue —
        // those rows go back to data admin, not forward to coordinator.
        [$sector, $kpi] = $this->seedKpi();
        $sectorHead = $this->makeSectorHead($sector);
        $facilitator = $this->makeFacilitator($sector);

        $this->makeTracking($kpi, [
            'quarter' => 1, 'actual_value' => '49', 'milestone' => '100',
            'sector_head_approved_by' => $sectorHead->id,
            'sector_head_approved_at' => now(),
            'facilitator_confirmed_by' => $facilitator->id,
            'facilitator_confirmed_at' => now(),
            'facilitator_decision' => 'Reject',
            'facilitator_rejection_reason' => 'Insufficient evidence',
            'confirmation_status' => 'Rejected',
        ]);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson('/api/v2/approvals/coordinator/queue')
            ->assertOk()->assertJsonCount(0);
    }

    public function test_facilitator_can_accept_when_confirmation_status_is_stale(): void
    {
        // Production scenario: web's older sector-head approval flow set
        // sector_head_approved_by but didn't update confirmation_status, so
        // the row reads as "Pending Sector Head Approval" even though the WHO
        // columns say it's awaiting facilitator. Mobile facilitator must still
        // be able to act on it (previously got 409 here).
        [$sector, $kpi] = $this->seedKpi();
        $sectorHead = $this->makeSectorHead($sector);
        $tracking = $this->makeTracking($kpi, [
            'quarter' => 1, 'actual_value' => '49', 'milestone' => '100',
            'sector_head_approved_by' => $sectorHead->id,
            'sector_head_approved_at' => now(),
            // Stale — should still be 'Pending Facilitator' but isn't.
            'confirmation_status' => 'Pending Sector Head Approval',
        ]);

        Passport::actingAs($this->makeFacilitator($sector), [], 'api');

        $this->postJson("/api/v2/approvals/submissions/{$tracking->id}/review", [
            'role' => 'facilitator',
            'decision' => 'accept',
            'validatedValue' => '49',
            'acceptRemarks' => 'confirmed',
        ])->assertStatus(202);

        $tracking->refresh();
        $this->assertSame('Pending Coordinator', $tracking->confirmation_status);
        $this->assertSame('Accept', $tracking->facilitator_decision);
        $this->assertSame('49', $tracking->delivery_department_value);
    }

    public function test_facilitator_review_409_when_sector_head_has_not_approved(): void
    {
        // The 409 is still correct when the row genuinely isn't ready for the
        // facilitator (no SH approval yet, regardless of status string).
        [$sector, $kpi] = $this->seedKpi();
        $this->makeTracking($kpi, [
            'quarter' => 1, 'actual_value' => '49', 'milestone' => '100',
            // No sector_head_approved_by set.
            'confirmation_status' => 'Pending Sector Head Approval',
        ]);
        $tracking = \App\Models\PerformanceTracking::where('kpi_id', $kpi->id)->first();

        Passport::actingAs($this->makeFacilitator($sector), [], 'api');

        $this->postJson("/api/v2/approvals/submissions/{$tracking->id}/review", [
            'role' => 'facilitator',
            'decision' => 'accept',
            'validatedValue' => '49',
        ])->assertStatus(409)->assertJsonPath('code', 'conflict');
    }

    public function test_facilitator_queue_narrows_to_requested_sector(): void
    {
        // Two assigned sectors with one pending row each.
        $fw = $this->makeFramework();
        $health = $this->makeSector($fw, ['sector_name' => 'Health']);
        $agri = $this->makeSector($fw, ['sector_name' => 'Agriculture']);
        $kpiHealth = $this->makeKpi($this->makeDeliverable($this->makeCommitment($health)));
        $kpiAgri = $this->makeKpi($this->makeDeliverable($this->makeCommitment($agri)));
        $this->tracking($kpiHealth, 'Pending Facilitator');
        $this->tracking($kpiAgri, 'Pending Facilitator');

        // Facilitator assigned to both sectors.
        $facilitator = $this->makeFacilitator($health);
        \App\Models\FacilitatorSector::create([
            'user_role_id' => $facilitator->getCurrentRole()->id,
            'sector_id' => $agri->id,
        ]);
        Passport::actingAs($facilitator, [], 'api');

        // No sector filter → both sectors appear as groups.
        $all = $this->getJson('/api/v2/approvals/facilitator/queue?grouping=by_sector')
            ->assertOk()->json();
        $this->assertCount(2, $all);

        // ?sector={agriId} → only Agriculture group.
        $scoped = $this->getJson("/api/v2/approvals/facilitator/queue?grouping=by_sector&sector={$agri->id}")
            ->assertOk()->json();
        $this->assertCount(1, $scoped);
        $this->assertSame('Agriculture', $scoped[0]['title']);

        // Asking for a sector NOT assigned to this facilitator (Education) — the
        // contract says ignore it, so we get the full assigned list back, not 403.
        $education = $this->makeSector($fw, ['sector_name' => 'Education']);
        $fallback = $this->getJson("/api/v2/approvals/facilitator/queue?grouping=by_sector&sector={$education->id}")
            ->assertOk()->json();
        $this->assertCount(2, $fallback);
    }

    public function test_data_admin_my_kpis(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $this->tracking($kpi, 'Pending Sector Head Approval', 1, ['year' => 2024]);

        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $this->getJson('/api/v2/approvals/data-admin/my-kpis?filter=all&year=2024')
            ->assertOk()->assertJsonCount(1)
            ->assertJsonStructure([['id', 'kpiId', 'title', 'categoryLabel', 'targetLabel', 'lastUpdateLabel', 'quarterStates', 'overallState']])
            ->assertJsonPath('0.quarterStates.0', 'pending_sector_head')
            ->assertJsonPath('0.overallState', 'pending_sector_head');
    }

    public function test_my_kpis_accepts_all_overall_state_filter_tokens(): void
    {
        // Seed one KPI per terminal/pending state and prove each filter token
        // returns only the matching row (and the wire's `pending_sh` alias
        // still maps to the full `pending_sector_head` state).
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => 'Health']);
        $commitment = $this->makeCommitment($sector);
        $deliverable = $this->makeDeliverable($commitment);
        $sh = $this->makeSectorHead($sector);
        $fc = $this->makeFacilitator($sector);
        $coordinator = $this->makeUser([], 'Coordinator');

        $shKpi = $this->makeKpi($deliverable, ['kpi' => 'A — Pending SH']);
        $facKpi = $this->makeKpi($deliverable, ['kpi' => 'B — Pending Facilitator']);
        $coKpi = $this->makeKpi($deliverable, ['kpi' => 'C — Pending Coordinator']);
        $confKpi = $this->makeKpi($deliverable, ['kpi' => 'D — Confirmed']);
        $rejKpi = $this->makeKpi($deliverable, ['kpi' => 'E — Rejected']);

        $year = 2024;

        $this->makeTracking($shKpi, ['quarter' => 1, 'year' => $year, 'milestone' => '100', 'actual_value' => '40']);

        $this->makeTracking($facKpi, [
            'quarter' => 1, 'year' => $year, 'milestone' => '100', 'actual_value' => '40',
            'sector_head_approved_by' => $sh->id, 'sector_head_approved_at' => now(),
        ]);

        $this->makeTracking($coKpi, [
            'quarter' => 1, 'year' => $year, 'milestone' => '100', 'actual_value' => '40',
            'sector_head_approved_by' => $sh->id, 'sector_head_approved_at' => now(),
            'facilitator_confirmed_by' => $fc->id, 'facilitator_confirmed_at' => now(),
            'facilitator_decision' => 'Accept',
        ]);

        // Confirmed: every quarter must read 'confirmed' for the rollup to be
        // 'confirmed' (other quarters with no rows default to pending_entry
        // and dominate).
        foreach ([1, 2, 3, 4] as $q) {
            $this->makeTracking($confKpi, [
                'quarter' => $q, 'year' => $year, 'milestone' => '100', 'actual_value' => '40',
                'sector_head_approved_by' => $sh->id, 'sector_head_approved_at' => now(),
                'facilitator_confirmed_by' => $fc->id, 'facilitator_confirmed_at' => now(),
                'facilitator_decision' => 'Accept',
                'coordinator_confirmed_by' => $coordinator->id, 'coordinator_confirmed_at' => now(),
            ]);
        }

        $this->makeTracking($rejKpi, [
            'quarter' => 1, 'year' => $year, 'milestone' => '100', 'actual_value' => '40',
            'sector_head_approved_by' => $sh->id, 'sector_head_approved_at' => now(),
            'facilitator_confirmed_by' => $fc->id, 'facilitator_confirmed_at' => now(),
            'facilitator_decision' => 'Reject',
            'facilitator_rejection_reason' => 'Evidence missing.',
        ]);

        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        // Each filter token narrows to exactly one of the seeded KPIs.
        $cases = [
            'pending_sh'           => $shKpi,
            'pending_sector_head'  => $shKpi, // alias of the above
            'pending_facilitator'  => $facKpi,
            'pending_coordinator'  => $coKpi,
            'confirmed'            => $confKpi,
            'rejected'             => $rejKpi,
        ];
        foreach ($cases as $filter => $expectedKpi) {
            $body = $this->getJson("/api/v2/approvals/data-admin/my-kpis?filter={$filter}&year=2024")
                ->assertOk()->json();
            $ids = array_column($body, 'kpiId');
            $this->assertSame(
                [(string) $expectedKpi->id], $ids,
                "filter={$filter} should return only kpi {$expectedKpi->id} (got: ".json_encode($ids).')'
            );
        }
    }

    public function test_my_kpis_reads_state_from_who_columns_when_confirmation_status_is_stale(): void
    {
        // The production scenario: data admin submitted, actual_value is set,
        // but the web's older path left confirmation_status at 'Not Confirmed'.
        // Without WHO derivation this row reads as 'pending_entry'; with the
        // fix it reads as 'pending_sector_head' (correct — waiting on SH).
        [$sector, $kpi] = $this->seedKpi();
        $this->makeTracking($kpi, [
            'quarter' => 1, 'year' => 2024,
            'milestone' => '100', 'actual_value' => '80',
            'sector_head_approved_by' => null,
            'facilitator_confirmed_by' => null,
            'coordinator_confirmed_by' => null,
            'confirmation_status' => 'Not Confirmed', // ← stale
        ]);

        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $this->getJson('/api/v2/approvals/data-admin/my-kpis?filter=all&year=2024')
            ->assertOk()
            ->assertJsonPath('0.quarterStates.0', 'pending_sector_head')
            ->assertJsonPath('0.overallState', 'pending_sector_head');
    }

    public function test_my_kpis_derives_pending_facilitator_when_sh_approved_but_status_stale(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $sh = $this->makeSectorHead($sector);
        $this->makeTracking($kpi, [
            'quarter' => 1, 'year' => 2024,
            'milestone' => '100', 'actual_value' => '80',
            'sector_head_approved_by' => $sh->id,
            'sector_head_approved_at' => now(),
            'confirmation_status' => 'Not Confirmed', // stale
        ]);

        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $this->getJson('/api/v2/approvals/data-admin/my-kpis?filter=all&year=2024')
            ->assertOk()
            ->assertJsonPath('0.quarterStates.0', 'pending_facilitator')
            ->assertJsonPath('0.overallState', 'pending_facilitator');
    }

    public function test_my_kpis_derives_pending_coordinator_when_facilitator_accepted_but_status_stale(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $sh = $this->makeSectorHead($sector);
        $fc = $this->makeFacilitator($sector);
        $this->makeTracking($kpi, [
            'quarter' => 1, 'year' => 2024,
            'milestone' => '100', 'actual_value' => '80',
            'sector_head_approved_by' => $sh->id, 'sector_head_approved_at' => now(),
            'facilitator_confirmed_by' => $fc->id, 'facilitator_confirmed_at' => now(),
            'facilitator_decision' => 'Accept',
            'confirmation_status' => 'Pending Facilitator', // stale — should be Pending Coordinator
        ]);

        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $this->getJson('/api/v2/approvals/data-admin/my-kpis?filter=all&year=2024')
            ->assertOk()
            ->assertJsonPath('0.quarterStates.0', 'pending_coordinator')
            ->assertJsonPath('0.overallState', 'pending_coordinator');
    }

    public function test_my_kpis_derives_rejected_when_facilitator_rejected(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $sh = $this->makeSectorHead($sector);
        $fc = $this->makeFacilitator($sector);
        $this->makeTracking($kpi, [
            'quarter' => 1, 'year' => 2024,
            'milestone' => '100', 'actual_value' => '80',
            'sector_head_approved_by' => $sh->id, 'sector_head_approved_at' => now(),
            'facilitator_confirmed_by' => $fc->id, 'facilitator_confirmed_at' => now(),
            'facilitator_decision' => 'Reject',
            'facilitator_rejection_reason' => 'Evidence missing.',
            'confirmation_status' => 'Pending Facilitator', // stale
        ]);

        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $body = $this->getJson('/api/v2/approvals/data-admin/my-kpis?filter=all&year=2024')
            ->assertOk()->json();

        $this->assertSame('rejected', $body[0]['quarterStates'][0]);
        $this->assertSame('rejected', $body[0]['overallState']);
        $this->assertSame('Action required', $body[0]['lastUpdateLabel']);
        $this->assertTrue($body[0]['lastUpdateIsError']);
    }

    public function test_my_kpis_derives_confirmed_when_coordinator_finalised(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $sh = $this->makeSectorHead($sector);
        $fc = $this->makeFacilitator($sector);
        $coordinator = $this->makeUser([], 'Coordinator');
        $this->makeTracking($kpi, [
            'quarter' => 1, 'year' => 2024,
            'milestone' => '100', 'actual_value' => '80',
            'sector_head_approved_by' => $sh->id, 'sector_head_approved_at' => now(),
            'facilitator_confirmed_by' => $fc->id, 'facilitator_confirmed_at' => now(),
            'facilitator_decision' => 'Accept',
            'coordinator_confirmed_by' => $coordinator->id, 'coordinator_confirmed_at' => now(),
            'confirmation_status' => 'Pending Coordinator', // stale — should be Confirmed
        ]);

        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        // Q1 is confirmed; the other 3 have no row → they're 'pending_entry'.
        // overallState rolls up to 'pending_entry' (other quarters still need
        // entry); the assertion focuses on the per-quarter derivation.
        $this->getJson('/api/v2/approvals/data-admin/my-kpis?filter=all&year=2024')
            ->assertOk()
            ->assertJsonPath('0.quarterStates.0', 'confirmed');
    }

    public function test_submission_detail(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $this->tracking($kpi, 'Pending Coordinator', 3, ['actual_value' => '512', 'milestone' => '350', 'remarks' => 'survey']);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson("/api/v2/approvals/submissions/{$kpi->id}")
            ->assertOk()
            ->assertJsonPath('kpiId', (string) $kpi->id)
            ->assertJsonPath('quarter', 'q3')
            ->assertJsonPath('state', 'pending_coordinator')
            ->assertJsonPath('actualValue', '512')
            ->assertJsonPath('targetValue', '85%')
            ->assertJsonStructure(['id', 'kpiTitle', 'sectorLabel', 'trackingDateLabel', 'milestoneValue', 'attachments']);
    }

    public function test_submission_detail_attachments_are_objects_with_url_for_images(): void
    {
        // Mobile contract: each attachments[*] is {name, url?} — url only
        // populated for image filenames (jpg/jpeg/png/gif/webp). Non-image
        // files (pdf etc) get just {name}.
        [$sector, $kpi] = $this->seedKpi();
        $t = $this->tracking($kpi, 'Pending Coordinator', 1, ['actual_value' => '50', 'milestone' => '100']);

        // Image evidence — should emit url.
        $img = new \App\Models\File();
        $img->name = 'site_photo.jpg';
        $img->path = 'uploads/evidence/ev_aaa.jpg';
        $img->type = 'jpg';
        $img->size = 12345;
        $img->fileable_id = $t->id;
        $img->fileable_type = \App\Models\PerformanceTracking::class;
        $img->save();

        // PDF evidence — no url, just name.
        $pdf = new \App\Models\File();
        $pdf->name = 'field_survey_q1.pdf';
        $pdf->path = 'uploads/evidence/ev_bbb.pdf';
        $pdf->type = 'pdf';
        $pdf->size = 54321;
        $pdf->fileable_id = $t->id;
        $pdf->fileable_type = \App\Models\PerformanceTracking::class;
        $pdf->save();

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $body = $this->getJson("/api/v2/approvals/submissions/{$kpi->id}")
            ->assertOk()->json();

        $byName = collect($body['attachments'])->keyBy('name');

        $this->assertSame('site_photo.jpg', $byName['site_photo.jpg']['name']);
        $this->assertArrayHasKey('url', $byName['site_photo.jpg']);
        $this->assertStringContainsString('uploads/evidence/ev_aaa.jpg', $byName['site_photo.jpg']['url']);

        $this->assertSame('field_survey_q1.pdf', $byName['field_survey_q1.pdf']['name']);
        $this->assertArrayNotHasKey('url', $byName['field_survey_q1.pdf']);
    }

    public function test_submission_detail_includes_delivery_verification_when_facilitator_accepted(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $sh = $this->makeSectorHead($sector);
        $fc = $this->makeFacilitator($sector);

        $this->makeTracking($kpi, [
            'quarter' => 3, 'year' => 2024,
            'milestone' => '100', 'actual_value' => '40',
            'sector_head_approved_by' => $sh->id, 'sector_head_approved_at' => now(),
            'facilitator_confirmed_by' => $fc->id,
            'facilitator_confirmed_at' => '2024-10-12 09:30:00',
            'facilitator_decision' => 'Accept',
            'delivery_department_value' => '41.0%',
            'delivery_department_remark' => 'Verified against on-site audit.',
            'confirmation_status' => 'Pending Coordinator',
        ]);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson("/api/v2/approvals/submissions/{$kpi->id}")
            ->assertOk()
            ->assertJsonPath('deliveryDateLabel', 'Oct 12, 2024')
            ->assertJsonPath('deliveryValue', '41.0%')
            ->assertJsonPath('deliveryRemarks', 'Verified against on-site audit.')
            ->assertJsonPath('deliveryAttachments', []);
    }

    public function test_submission_detail_omits_delivery_fields_before_facilitator_acts(): void
    {
        // A row that's only past sector-head (still awaiting facilitator) has
        // no delivery-department verification yet. The four delivery-* fields
        // must be omitted entirely.
        [$sector, $kpi] = $this->seedKpi();
        $sh = $this->makeSectorHead($sector);

        $this->makeTracking($kpi, [
            'quarter' => 3, 'year' => 2024,
            'milestone' => '100', 'actual_value' => '40',
            'sector_head_approved_by' => $sh->id, 'sector_head_approved_at' => now(),
            // No facilitator action yet.
            'facilitator_confirmed_by' => null,
            'confirmation_status' => 'Pending Facilitator',
        ]);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $body = $this->getJson("/api/v2/approvals/submissions/{$kpi->id}")
            ->assertOk()->json();

        $this->assertArrayNotHasKey('deliveryDateLabel', $body);
        $this->assertArrayNotHasKey('deliveryValue', $body);
        $this->assertArrayNotHasKey('deliveryRemarks', $body);
        $this->assertArrayNotHasKey('deliveryAttachments', $body);
    }

    public function test_sector_head_accept_advances_to_facilitator(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $t = $this->tracking($kpi, 'Pending Sector Head Approval');
        $sh = $this->makeSectorHead($sector);
        Passport::actingAs($sh, [], 'api');

        $this->postJson("/api/v2/approvals/submissions/{$t->id}/review", ['role' => 'sector_head', 'decision' => 'accept'])
            ->assertStatus(202);

        $t->refresh();
        $this->assertSame('Pending Facilitator', $t->confirmation_status);
        $this->assertSame($sh->id, (int) $t->sector_head_approved_by);
    }

    public function test_facilitator_accept_sets_delivery_value_and_advances(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $t = $this->tracking($kpi, 'Pending Facilitator');
        Passport::actingAs($this->makeFacilitator($sector), [], 'api');

        $this->postJson("/api/v2/approvals/submissions/{$t->id}/review", [
            'role' => 'facilitator', 'decision' => 'accept', 'validatedValue' => '512', 'acceptRemarks' => 'verified',
        ])->assertStatus(202);

        $t->refresh();
        $this->assertSame('Pending Coordinator', $t->confirmation_status);
        $this->assertSame('Accept', $t->facilitator_decision);
        $this->assertSame('512', $t->delivery_department_value);
    }

    public function test_coordinator_accept_confirms_and_reject_sets_reason(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $coordinator = $this->makeUser([], 'Coordinator');

        $accepted = $this->tracking($kpi, 'Pending Coordinator', 1);
        Passport::actingAs($coordinator, [], 'api');
        $this->postJson("/api/v2/approvals/submissions/{$accepted->id}/review", ['role' => 'coordinator', 'decision' => 'accept'])
            ->assertStatus(202);
        $this->assertSame('Confirmed', $accepted->refresh()->confirmation_status);

        $rejected = $this->tracking($kpi, 'Pending Coordinator', 2);
        $this->postJson("/api/v2/approvals/submissions/{$rejected->id}/review", ['role' => 'coordinator', 'decision' => 'reject', 'rejectionReason' => 'Insufficient evidence'])
            ->assertStatus(202);
        $rejected->refresh();
        $this->assertSame('Rejected', $rejected->confirmation_status);
        $this->assertSame('Insufficient evidence', $rejected->coordinator_rejection_reason);
    }

    public function test_review_conflicts_when_state_mismatch(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $t = $this->tracking($kpi, 'Pending Coordinator'); // not awaiting sector head
        Passport::actingAs($this->makeSectorHead($sector), [], 'api');

        $this->postJson("/api/v2/approvals/submissions/{$t->id}/review", ['role' => 'sector_head', 'decision' => 'accept'])
            ->assertStatus(409)->assertJsonPath('code', 'conflict');
    }

    public function test_review_forbidden_when_role_not_held(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $t = $this->tracking($kpi, 'Pending Sector Head Approval');
        // Data Admin can access the sector but is not a sector head.
        Passport::actingAs($this->makeDataAdmin($sector), [], 'api');

        $this->postJson("/api/v2/approvals/submissions/{$t->id}/review", ['role' => 'sector_head', 'decision' => 'accept'])
            ->assertStatus(403)->assertJsonPath('code', 'forbidden');
    }

    public function test_review_validation_errors(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $t = $this->tracking($kpi, 'Pending Coordinator');
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson("/api/v2/approvals/submissions/{$t->id}/review", [])
            ->assertStatus(422)
            ->assertJsonStructure(['fieldErrors' => ['role', 'decision']]);
    }

    public function test_sector_head_queue_bulk_and_bulk_approve_share_the_same_id_space(): void
    {
        // The mobile client carries the sector head's queue selection into
        // the bulk-approval page. That only works if the same submission has
        // the same id across queue + bulk + bulk-approve. Three rows here,
        // assert the round-trip.
        [$sector, $kpi] = $this->seedKpi();
        $t1 = $this->tracking($kpi, 'Pending Sector Head Approval', 1);
        $t2 = $this->tracking($kpi, 'Pending Sector Head Approval', 2);
        $t3 = $this->tracking($kpi, 'Pending Sector Head Approval', 3);

        Passport::actingAs($this->makeSectorHead($sector), [], 'api');

        $queueIds = collect($this->getJson('/api/v2/approvals/sector-head/queue')
            ->assertOk()->json())
            ->pluck('id')->sort()->values()->all();

        $bulkIds = collect($this->getJson('/api/v2/approvals/sector-head/bulk?grouping=by_commitment')
            ->assertOk()->json('groups'))
            ->flatMap(fn ($g) => array_column($g['items'], 'id'))
            ->sort()->values()->all();

        $expected = collect([$t1, $t2, $t3])->pluck('id')->map(fn ($i) => (string) $i)->sort()->values()->all();

        $this->assertSame($expected, $queueIds, 'queue ids must match the submission ids');
        $this->assertSame($expected, $bulkIds, 'bulk ids must match the submission ids');

        // Sending the queue ids straight to bulk-approve should land all three.
        $this->postJson('/api/v2/approvals/submissions/bulk-approve', [
            'submissionIds' => $queueIds,
            'role' => 'sector_head',
        ])->assertStatus(202);

        $this->assertSame('Pending Facilitator', $t1->refresh()->confirmation_status);
        $this->assertSame('Pending Facilitator', $t2->refresh()->confirmation_status);
        $this->assertSame('Pending Facilitator', $t3->refresh()->confirmation_status);
    }

    public function test_coordinator_queue_and_bulk_approve_share_the_same_id_space(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $t1 = $this->tracking($kpi, 'Pending Coordinator', 1);
        $t2 = $this->tracking($kpi, 'Pending Coordinator', 2);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $queueIds = collect($this->getJson('/api/v2/approvals/coordinator/queue')
            ->assertOk()->json())
            ->pluck('id')->sort()->values()->all();

        $expected = collect([$t1, $t2])->pluck('id')->map(fn ($i) => (string) $i)->sort()->values()->all();
        $this->assertSame($expected, $queueIds);

        $this->postJson('/api/v2/approvals/submissions/bulk-approve', [
            'submissionIds' => $queueIds,
            'role' => 'coordinator',
        ])->assertStatus(202);

        $this->assertSame('Confirmed', $t1->refresh()->confirmation_status);
        $this->assertSame('Confirmed', $t2->refresh()->confirmation_status);
    }

    public function test_bulk_approve_advances_all(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $t1 = $this->tracking($kpi, 'Pending Sector Head Approval', 1);
        $t2 = $this->tracking($kpi, 'Pending Sector Head Approval', 2);
        Passport::actingAs($this->makeSectorHead($sector), [], 'api');

        $this->postJson('/api/v2/approvals/submissions/bulk-approve', [
            'submissionIds' => [(string) $t1->id, (string) $t2->id], 'role' => 'sector_head',
        ])->assertStatus(202);

        $this->assertSame('Pending Facilitator', $t1->refresh()->confirmation_status);
        $this->assertSame('Pending Facilitator', $t2->refresh()->confirmation_status);
    }

    public function test_bulk_approve_coordinator_finalizes_all(): void
    {
        [$sector, $kpi] = $this->seedKpi();
        $t1 = $this->tracking($kpi, 'Pending Coordinator', 1);
        $t2 = $this->tracking($kpi, 'Pending Coordinator', 2);
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson('/api/v2/approvals/submissions/bulk-approve', [
            'submissionIds' => [(string) $t1->id, (string) $t2->id],
            'role' => 'coordinator',
        ])->assertStatus(202);

        $t1->refresh();
        $t2->refresh();
        $this->assertSame('Confirmed', $t1->confirmation_status);
        $this->assertSame('Confirmed', $t2->confirmation_status);
        $this->assertNotNull($t1->coordinator_confirmed_by);
        $this->assertNotNull($t2->coordinator_confirmed_by);
    }

    public function test_bulk_approve_coordinator_is_atomic_when_one_row_is_wrong_stage(): void
    {
        // One row is genuinely pending coordinator; the other is still at the
        // sector head stage. The whole call must 409 and leave BOTH unchanged.
        [$sector, $kpi] = $this->seedKpi();
        $ready = $this->tracking($kpi, 'Pending Coordinator', 1);
        $earlyStage = $this->tracking($kpi, 'Pending Sector Head Approval', 2);

        $readyStatusBefore = $ready->confirmation_status;
        $earlyStatusBefore = $earlyStage->confirmation_status;

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson('/api/v2/approvals/submissions/bulk-approve', [
            'submissionIds' => [(string) $ready->id, (string) $earlyStage->id],
            'role' => 'coordinator',
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'conflict');

        $this->assertSame($readyStatusBefore, $ready->refresh()->confirmation_status);
        $this->assertSame($earlyStatusBefore, $earlyStage->refresh()->confirmation_status);
    }

    public function test_bulk_approve_coordinator_role_requires_coordinator_token(): void
    {
        // A sector head trying to finalize as coordinator must be rejected.
        [$sector, $kpi] = $this->seedKpi();
        $t = $this->tracking($kpi, 'Pending Coordinator', 1);
        Passport::actingAs($this->makeSectorHead($sector), [], 'api');

        $this->postJson('/api/v2/approvals/submissions/bulk-approve', [
            'submissionIds' => [(string) $t->id],
            'role' => 'coordinator',
        ])->assertStatus(403)->assertJsonPath('code', 'forbidden');

        $this->assertSame('Pending Coordinator', $t->refresh()->confirmation_status);
    }

    public function test_bulk_approve_rejects_facilitator_role(): void
    {
        // Bulk is supported for sector_head and coordinator only.
        [$sector, $kpi] = $this->seedKpi();
        $t = $this->tracking($kpi, 'Pending Facilitator', 1);
        Passport::actingAs($this->makeFacilitator($sector), [], 'api');

        $this->postJson('/api/v2/approvals/submissions/bulk-approve', [
            'submissionIds' => [(string) $t->id],
            'role' => 'facilitator',
        ])->assertStatus(422)->assertJsonPath('code', 'validation_error');
    }

    public function test_queue_requires_auth(): void
    {
        $this->getJson('/api/v2/approvals/coordinator/queue')->assertStatus(401)->assertJsonPath('code', 'unauthenticated');
    }
}
