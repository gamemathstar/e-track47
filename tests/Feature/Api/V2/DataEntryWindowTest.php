<?php

namespace Tests\Feature\Api\V2;

use App\Models\DataEntryAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

/**
 * §11.7 data-entry windows: list / stats / lock-unlock / open-lock-override.
 * Coordinator-only (403 otherwise).
 */
class DataEntryWindowTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPdcuAuth;

    public function test_list_windows_seeds_rows_for_active_framework_sectors(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => 'Health']);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson('/api/v2/data-entry/windows')
            ->assertOk()->assertJsonCount(1)
            ->assertJsonStructure([['sectorId', 'sectorName', 'accent', 'status', 'lastUpdatedLabel', 'quarterLabel', 'deadlineLabel']])
            ->assertJsonPath('0.status', 'locked'); // default seeded status
    }

    public function test_stats_returns_counts(): void
    {
        $fw = $this->makeFramework();
        $this->makeSector($fw, ['sector_name' => 'Health']);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson('/api/v2/data-entry/stats')
            ->assertOk()
            ->assertJsonStructure(['totalSectors', 'openSectors', 'submissionRateLabel'])
            ->assertJsonPath('totalSectors', 1);
    }

    public function test_unlock_and_lock_per_sector(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw);
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson("/api/v2/data-entry/windows/{$sector->id}/open")->assertStatus(202);
        $this->assertSame('open', DataEntryAccess::where('sector_id', $sector->id)->first()->status);

        $this->postJson("/api/v2/data-entry/windows/{$sector->id}/lock")->assertStatus(202);
        $this->assertSame('closed', DataEntryAccess::where('sector_id', $sector->id)->first()->status);
    }

    public function test_grant_override(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw);
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson("/api/v2/data-entry/windows/{$sector->id}/override", [
            'reason' => 'Late submission approved',
            'expiresAt' => '2024-12-31T23:59:59.000',
        ])->assertStatus(202);

        $row = DataEntryAccess::where('sector_id', $sector->id)->first();
        $this->assertSame('override', $row->status);
        $this->assertSame('Late submission approved', $row->override_reason);
        $this->assertNotNull($row->override_deadline);
    }

    public function test_lock_all_and_unlock_all(): void
    {
        $fw = $this->makeFramework(['year' => 2024]);
        $this->makeSector($fw, ['sector_name' => 'A']);
        $this->makeSector($fw, ['sector_name' => 'B']);
        $coordinator = $this->makeUser([], 'Coordinator');
        Passport::actingAs($coordinator, [], 'api');

        // unlock-all now requires reason + year + quarter; optional expiresAt.
        // Marks every sector window as override (audited grant), stamps reason +
        // granted_by/granted_at.
        $this->postJson('/api/v2/data-entry/windows/unlock-all', [
            'reason' => 'Bulk reopen after deadline extension',
            'year' => 2024,
            'quarter' => 'q1',
            'expiresAt' => '2024-04-30T23:59:59.000',
        ])->assertStatus(202);

        $overrides = DataEntryAccess::where('year', 2024)->where('quarter', 1)->where('status', 'override')->get();
        $this->assertCount(2, $overrides);
        $this->assertSame('Bulk reopen after deadline extension', $overrides->first()->override_reason);
        $this->assertSame($coordinator->id, (int) $overrides->first()->granted_by);
        $this->assertNotNull($overrides->first()->override_deadline);

        // lock-all requires year + quarter; flips every row to closed.
        $this->postJson('/api/v2/data-entry/windows/lock-all', [
            'year' => 2024,
            'quarter' => 'q1',
        ])->assertStatus(202);

        $this->assertSame(2, DataEntryAccess::where('year', 2024)->where('quarter', 1)->where('status', 'closed')->count());
    }

    public function test_lock_all_requires_year_and_quarter(): void
    {
        $fw = $this->makeFramework();
        $this->makeSector($fw);
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson('/api/v2/data-entry/windows/lock-all', [])
            ->assertStatus(422)
            ->assertJsonStructure(['fieldErrors' => ['year', 'quarter']]);
    }

    public function test_unlock_all_requires_reason_year_and_quarter(): void
    {
        $fw = $this->makeFramework();
        $this->makeSector($fw);
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson('/api/v2/data-entry/windows/unlock-all', [])
            ->assertStatus(422)
            ->assertJsonStructure(['fieldErrors' => ['reason', 'year', 'quarter']]);
    }

    public function test_grant_override_validation(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw);
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson("/api/v2/data-entry/windows/{$sector->id}/override", [])
            ->assertStatus(422)->assertJsonStructure(['fieldErrors' => ['reason']]);
    }

    public function test_non_coordinator_forbidden(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw);
        Passport::actingAs($this->makeSectorHead($sector), [], 'api');

        $this->getJson('/api/v2/data-entry/windows')->assertStatus(403)->assertJsonPath('code', 'forbidden');
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v2/data-entry/windows')->assertStatus(401);
    }

    public function test_stats_scopes_to_the_year_framework_not_the_active_one(): void
    {
        // Two frameworks: 2023 (Archived) with one sector, 2024 (Active) with two.
        $fw2023 = $this->makeFramework(['year' => 2023, 'status' => 'Archived']);
        // makeFramework() archives all other frameworks; recreate active 2024 last.
        $fw2024 = $this->makeFramework(['year' => 2024, 'status' => 'Active']);
        $fw2023->refresh();

        $this->makeSector($fw2023, ['sector_name' => 'Legacy Health']);
        $this->makeSector($fw2024, ['sector_name' => 'Health']);
        $this->makeSector($fw2024, ['sector_name' => 'Education']);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        // Querying 2023 should report 1 sector (Legacy Health), NOT 2024's two.
        $this->getJson('/api/v2/data-entry/stats?year=2023&quarter=q1')
            ->assertOk()->assertJsonPath('totalSectors', 1);

        // Querying 2024 should report 2 sectors.
        $this->getJson('/api/v2/data-entry/stats?year=2024&quarter=q1')
            ->assertOk()->assertJsonPath('totalSectors', 2);
    }

}
