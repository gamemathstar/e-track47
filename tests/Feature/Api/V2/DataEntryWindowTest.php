<?php

namespace Tests\Feature\Api\V2;

use App\Jobs\SendFcmJob;
use App\Models\DataEntryAccess;
use App\Models\DeviceToken;
use App\Models\Notification;
use App\Services\V2\Notifications\NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
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

    // --- window-change notifications -----------------------------------------

    public function test_opening_window_notifies_sector_participants(): void
    {
        Bus::fake([SendFcmJob::class]);

        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => 'Health']);
        $dataAdmin = $this->makeDataAdmin($sector);
        $sectorHead = $this->makeSectorHead($sector);
        $facilitator = $this->makeFacilitator($sector);

        // Give each participant a device token so push jobs queue.
        foreach ([$dataAdmin, $sectorHead, $facilitator] as $i => $u) {
            DeviceToken::create([
                'user_id' => $u->id,
                'token' => str_repeat(chr(97 + $i), 64),
                'platform' => 'android',
            ]);
        }

        $coordinator = $this->makeUser([], 'Coordinator');
        Passport::actingAs($coordinator, [], 'api');

        $this->postJson("/api/v2/data-entry/windows/{$sector->id}/open")->assertStatus(202);

        // Each participant gets an inbox row.
        foreach ([$dataAdmin, $sectorHead, $facilitator] as $u) {
            $inbox = Notification::where('user_id', $u->id)->first();
            $this->assertNotNull($inbox, "user {$u->id} should have an inbox row");
            $this->assertSame('Data entry window opened', $inbox->title);
            $this->assertSame('deadline', $inbox->type);
            $this->assertStringContainsString('Health', $inbox->body);
        }

        // Coordinator (the actor) gets NO row — they triggered the action.
        $this->assertSame(0, Notification::where('user_id', $coordinator->id)->count());

        // One FCM job per device token (3).
        Bus::assertDispatchedTimes(SendFcmJob::class, 3);
    }

    public function test_override_grant_notification_includes_reason_and_expiry(): void
    {
        Bus::fake([SendFcmJob::class]);

        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => 'Education']);
        $dataAdmin = $this->makeDataAdmin($sector);
        // Register a device for the recipient so the FCM dispatch path
        // is exercised — without this the test would silently skip the
        // push side and pass even if FCM was broken.
        DeviceToken::create([
            'user_id' => $dataAdmin->id,
            'token' => str_repeat('o', 64),
            'platform' => 'android',
        ]);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson("/api/v2/data-entry/windows/{$sector->id}/override", [
            'reason' => 'Late submission approved by coordinator',
            'expiresAt' => '2024-12-31T23:59:59.000',
        ])->assertStatus(202);

        $inbox = Notification::where('user_id', $dataAdmin->id)->first();
        $this->assertNotNull($inbox);
        $this->assertSame('Data entry override granted', $inbox->title);
        $this->assertStringContainsString('Education', $inbox->body);
        $this->assertStringContainsString('Late submission approved by coordinator', $inbox->body);
        $this->assertStringContainsString('31 Dec 2024', $inbox->body);

        // The actual bug repro: this assert should pass — if the inbox row
        // was written but no FCM job got queued for a recipient with a
        // registered device, the dispatch chain is broken.
        Bus::assertDispatched(SendFcmJob::class);
    }

    public function test_unlock_all_fans_out_per_sector(): void
    {
        Bus::fake([SendFcmJob::class]);

        $fw = $this->makeFramework(['year' => 2024]);
        $sectorA = $this->makeSector($fw, ['sector_name' => 'A']);
        $sectorB = $this->makeSector($fw, ['sector_name' => 'B']);
        $daA = $this->makeDataAdmin($sectorA);
        $daB = $this->makeDataAdmin($sectorB);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson('/api/v2/data-entry/windows/unlock-all', [
            'reason' => 'Bulk reopen',
            'year' => 2024, 'quarter' => 'q1',
        ])->assertStatus(202);

        // Each sector's data admin gets an inbox row mentioning their sector.
        $rowA = Notification::where('user_id', $daA->id)->first();
        $rowB = Notification::where('user_id', $daB->id)->first();
        $this->assertNotNull($rowA);
        $this->assertNotNull($rowB);
        $this->assertStringContainsString('A', $rowA->body);
        $this->assertStringContainsString('B', $rowB->body);
        $this->assertSame('Data entry override granted', $rowA->title);
    }

    public function test_window_copy_produces_expected_strings(): void
    {
        [$t1, $b1] = NotificationDispatcher::windowCopy(
            NotificationDispatcher::STAGE_WINDOW_OPENED,
            ['sectorName' => 'Health', 'quarter' => 3, 'year' => 2024],
        );
        $this->assertSame('Data entry window opened', $t1);
        $this->assertStringContainsString('Health (Q3 2024)', $b1);
        $this->assertStringContainsString('Submit your performance values', $b1);

        [$t2, $b2] = NotificationDispatcher::windowCopy(
            NotificationDispatcher::STAGE_WINDOW_OVERRIDE_GRANTED,
            [
                'sectorName' => 'Health',
                'quarter' => 3, 'year' => 2024,
                'reason' => 'Late approval',
                'expiresAt' => '2024-12-31T23:59:59Z',
            ],
        );
        $this->assertSame('Data entry override granted', $t2);
        $this->assertStringContainsString('open via override until 31 Dec 2024', $b2);
        $this->assertStringContainsString('Reason: Late approval.', $b2);
    }

    public function test_sector_participants_excludes_coordinators(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw);
        $da = $this->makeDataAdmin($sector);
        $sh = $this->makeSectorHead($sector);
        $fac = $this->makeFacilitator($sector);
        $coordinator = $this->makeUser([], 'Coordinator');

        $resolved = app(NotificationDispatcher::class)->sectorParticipantsFor($sector->id);
        $ids = $resolved->pluck('id')->all();

        $this->assertContains((int) $da->id, $ids);
        $this->assertContains((int) $sh->id, $ids);
        $this->assertContains((int) $fac->id, $ids);
        $this->assertNotContains((int) $coordinator->id, $ids,
            'Coordinators are actors, not recipients of window notifications');
    }

    // --- lock notifications --------------------------------------------------

    public function test_locking_window_notifies_sector_participants(): void
    {
        Bus::fake([SendFcmJob::class]);

        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => 'Health']);
        $dataAdmin = $this->makeDataAdmin($sector);
        DeviceToken::create([
            'user_id' => $dataAdmin->id,
            'token' => str_repeat('q', 64),
            'platform' => 'android',
        ]);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        // Pre-open the window so lock is a meaningful transition.
        $this->postJson("/api/v2/data-entry/windows/{$sector->id}/open")->assertStatus(202);
        // Clear the opened-notification — use DELETE not TRUNCATE so we stay
        // inside RefreshDatabase's transaction (TRUNCATE is DDL on MySQL and
        // implicitly commits, leaking state into subsequent tests).
        Notification::query()->delete();

        $this->postJson("/api/v2/data-entry/windows/{$sector->id}/lock")->assertStatus(202);

        $inbox = Notification::where('user_id', $dataAdmin->id)->first();
        $this->assertNotNull($inbox);
        $this->assertSame('Data entry window closed', $inbox->title);
        $this->assertSame('deadline', $inbox->type);
        $this->assertStringContainsString('Health', $inbox->body);
        $this->assertStringContainsString('submission window has ended', $inbox->body);

        Bus::assertDispatched(SendFcmJob::class);
    }

    public function test_lock_all_fans_out_per_sector(): void
    {
        Bus::fake([SendFcmJob::class]);

        $fw = $this->makeFramework(['year' => 2024]);
        $sectorA = $this->makeSector($fw, ['sector_name' => 'A']);
        $sectorB = $this->makeSector($fw, ['sector_name' => 'B']);
        $daA = $this->makeDataAdmin($sectorA);
        $daB = $this->makeDataAdmin($sectorB);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson('/api/v2/data-entry/windows/lock-all', [
            'year' => 2024, 'quarter' => 'q1',
        ])->assertStatus(202);

        $rowA = Notification::where('user_id', $daA->id)->first();
        $rowB = Notification::where('user_id', $daB->id)->first();
        $this->assertNotNull($rowA);
        $this->assertNotNull($rowB);
        $this->assertSame('Data entry window closed', $rowA->title);
        $this->assertStringContainsString('A', $rowA->body);
        $this->assertStringContainsString('B', $rowB->body);
    }

    public function test_window_copy_lock_stage(): void
    {
        [$title, $body] = NotificationDispatcher::windowCopy(
            NotificationDispatcher::STAGE_WINDOW_LOCKED,
            ['sectorName' => 'Health', 'quarter' => 4, 'year' => 2024],
        );
        $this->assertSame('Data entry window closed', $title);
        $this->assertStringContainsString('Health (Q4 2024)', $body);
        $this->assertStringContainsString('submission window has ended', $body);
    }

    // --- deep link payload ---------------------------------------------------

    public function test_window_notifications_carry_data_entry_deep_link(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => 'Health']);
        $dataAdmin = $this->makeDataAdmin($sector);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        // Open → deep link present on the inbox row.
        $this->postJson("/api/v2/data-entry/windows/{$sector->id}/open")->assertStatus(202);
        $inbox = Notification::where('user_id', $dataAdmin->id)->first();
        $this->assertNotNull($inbox);
        $this->assertSame('dataEntryWindow', $inbox->deep_link_route);

        $params = is_string($inbox->deep_link_params)
            ? json_decode($inbox->deep_link_params, true)
            : $inbox->deep_link_params;
        $this->assertSame((string) $sector->id, $params['sectorId']);
        $this->assertMatchesRegularExpression('/^\d{4}$/', $params['year']);
        $this->assertMatchesRegularExpression('/^q[1-4]$/', $params['quarter']);
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
