<?php

namespace Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

/**
 * N+1 audit for the heaviest list/queue/dashboard/viewer endpoints. Seeds a
 * fixed-size hierarchy (3 sectors × 2 commitments × 2 deliverables × 2 KPIs +
 * 1 tracking each) and asserts each endpoint runs in a **bounded** number of
 * queries — independent of row count, so we catch N+1 regressions early.
 */
class QueryCountAuditTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPdcuAuth;

    private array $sectors = [];

    protected function setUp(): void
    {
        parent::setUp();
        $fw = $this->makeFramework();
        for ($s = 0; $s < 3; $s++) {
            $sector = $this->makeSector($fw, ['sector_name' => 'Sector '.$s]);
            $this->sectors[] = $sector;
            for ($c = 0; $c < 2; $c++) {
                $commitment = $this->makeCommitment($sector, ['name' => "Commitment {$s}.{$c}"]);
                for ($d = 0; $d < 2; $d++) {
                    $deliverable = $this->makeDeliverable($commitment, ['deliverable' => "Deliverable {$s}.{$c}.{$d}"]);
                    for ($k = 0; $k < 2; $k++) {
                        $kpi = $this->makeKpi($deliverable, ['kpi' => "KPI {$s}.{$c}.{$d}.{$k}"]);
                        $this->makeTracking($kpi, ['quarter' => 1, 'actual_value' => '80', 'milestone' => '100']);
                    }
                }
            }
        }
    }

    /** Run a closure with query logging and return the count. */
    private function countQueries(callable $action): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $action();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_sectors_list_is_bounded(): void
    {
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $queries = $this->countQueries(fn () => $this->getJson('/api/v2/sectors')->assertOk());
        $this->assertLessThan(15, $queries, "GET /sectors used {$queries} queries (expected <15)");
    }

    public function test_sector_head_queue_is_bounded(): void
    {
        // Approve trackings to sector-head-pending state so the queue has rows.
        \App\Models\PerformanceTracking::query()->update(['confirmation_status' => 'Pending Sector Head Approval']);
        Passport::actingAs($this->makeSectorHead($this->sectors[0]), [], 'api');

        $queries = $this->countQueries(fn () => $this->getJson('/api/v2/approvals/sector-head/queue')->assertOk());
        $this->assertLessThan(20, $queries, "GET /approvals/sector-head/queue used {$queries} queries (expected <20)");
    }

    public function test_governor_dashboard_is_bounded(): void
    {
        Passport::actingAs($this->makeUser(['target_entity' => 'State'], 'Governor'), [], 'api');

        $queries = $this->countQueries(fn () => $this->getJson('/api/v2/dashboard/governor')->assertOk());
        $this->assertLessThan(30, $queries, "GET /dashboard/governor used {$queries} queries (expected <30)");
    }

    public function test_reports_viewer_is_bounded(): void
    {
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $queries = $this->countQueries(fn () => $this->postJson('/api/v2/reports/viewer', [
            'sectorIds' => [], 'year' => 2024, 'includeEvidence' => true,
        ])->assertOk());
        // Viewer iterates KPIs per sector; growth should be linear in sectors, not in KPIs.
        $this->assertLessThan(80, $queries, "POST /reports/viewer used {$queries} queries (expected <80 for 3 sectors × 2 commitments × 2 deliverables × 2 KPIs)");
    }

    public function test_users_list_is_bounded(): void
    {
        // Seed a handful of users with roles.
        for ($i = 0; $i < 5; $i++) {
            $this->makeUser(['email' => "u{$i}@pdcu.gov.ng"], 'Facilitator');
        }
        Passport::actingAs($this->makeUser(['target_entity' => 'System'], 'System Admin'), [], 'api');

        $queries = $this->countQueries(fn () => $this->getJson('/api/v2/users')->assertOk());
        $this->assertLessThan(40, $queries, "GET /users used {$queries} queries (expected <40)");
    }
}
