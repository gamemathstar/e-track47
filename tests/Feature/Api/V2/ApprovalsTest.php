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
            ->assertJsonStructure([['title', 'items' => [['id', 'title', 'value', 'adminName']]]])
            ->assertJsonPath('0.title', 'Commitment: Maternal Health Expansion');
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
            ->assertJsonStructure([['id', 'title', 'accent', 'items' => [['id', 'kpiId', 'state']]]])
            ->assertJsonPath('0.items.0.state', 'pending_facilitator');
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

    public function test_queue_requires_auth(): void
    {
        $this->getJson('/api/v2/approvals/coordinator/queue')->assertStatus(401)->assertJsonPath('code', 'unauthenticated');
    }
}
