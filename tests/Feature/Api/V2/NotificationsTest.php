<?php

namespace Tests\Feature\Api\V2;

use App\Jobs\SendFcmJob;
use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\UserRole;
use App\Services\V2\Notifications\FcmTransport;
use App\Services\V2\Notifications\NotificationDispatcher;
use App\Services\V2\Notifications\NullFcmTransport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Passport\Passport;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

/**
 * §11.14 notifications: inbox grouping + preferences + mark-read.
 */
class NotificationsTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPdcuAuth;

    private function seedNotification(int $userId, array $attrs = []): Notification
    {
        $n = new Notification();
        $n->user_id = $userId;
        $n->sender_id = $attrs['sender_id'] ?? 0;
        $n->type = $attrs['type'] ?? 'Approval Required';
        $n->title = $attrs['title'] ?? 'Title';
        $n->body = $attrs['body'] ?? 'Body';
        $n->model_id = $attrs['model_id'] ?? 0;
        $n->status = $attrs['status'] ?? 'Not Read';
        if (array_key_exists('kind', $attrs)) {
            $n->kind = $attrs['kind'];
        }
        $n->save();

        return $n;
    }

    public function test_inbox_groups_and_requires_tab(): void
    {
        $user = $this->makeUser();
        $this->seedNotification($user->id, ['type' => 'Approval Required']);
        $this->seedNotification($user->id, ['type' => 'KPI Submission', 'status' => 'Read']);

        Passport::actingAs($user, [], 'api');

        $this->getJson('/api/v2/notifications/inbox')
            ->assertStatus(422)->assertJsonStructure(['fieldErrors' => ['tab']]);

        $res = $this->getJson('/api/v2/notifications/inbox?tab=all')->assertOk();
        $res->assertJsonStructure(['sections' => [['id', 'label', 'notifications']]])
            ->assertJsonPath('sections.0.id', 'today');

        // Today section has 2 items.
        $this->assertCount(2, $res->json('sections.0.notifications'));

        // unread tab → 1 item.
        $this->getJson('/api/v2/notifications/inbox?tab=unread')
            ->assertOk();
        $resp = $this->getJson('/api/v2/notifications/inbox?tab=unread')->json();
        $this->assertCount(1, $resp['sections'][0]['notifications']);
    }

    public function test_inbox_item_shape_and_kind_mapping(): void
    {
        $user = $this->makeUser();
        $this->seedNotification($user->id, ['type' => 'Approval Required', 'title' => 'KPI Approval Required']);

        Passport::actingAs($user, [], 'api');

        $payload = $this->getJson('/api/v2/notifications/inbox?tab=all')->json();
        $item = $payload['sections'][0]['notifications'][0];

        $this->assertSame('approval', $item['kind']);
        $this->assertSame('check_circle', $item['iconKey']);
        $this->assertSame('primary', $item['accent']);
        $this->assertTrue($item['isUnread']);
        $this->assertSame('KPI Approval Required', $item['title']);
    }

    public function test_preferences_defaults_then_update(): void
    {
        $user = $this->makeUser();
        Passport::actingAs($user, [], 'api');

        $defaults = $this->getJson('/api/v2/notifications/preferences')->assertOk()->json();
        $this->assertTrue($defaults['submissions']);
        $this->assertFalse($defaults['sms']);
        $this->assertSame(22, $defaults['quietFrom']['hour']);

        $this->putJson('/api/v2/notifications/preferences', [
            'submissions' => true, 'approvals' => true, 'rejections' => false,
            'mentions' => true, 'deadlines' => true,
            'push' => true, 'email' => false, 'sms' => false,
            'quietHoursEnabled' => true,
            'quietFrom' => ['hour' => 21, 'minute' => 30],
            'quietTo' => ['hour' => 7, 'minute' => 0],
        ])->assertNoContent();

        $row = NotificationPreference::where('user_id', $user->id)->first();
        $this->assertFalse((bool) $row->rejections);
        $this->assertTrue((bool) $row->quiet_hours_enabled);
        $this->assertSame(21, (int) $row->quiet_from_hour);
    }

    public function test_preferences_update_validation(): void
    {
        Passport::actingAs($this->makeUser(), [], 'api');

        $this->putJson('/api/v2/notifications/preferences', [])
            ->assertStatus(422)
            ->assertJsonStructure(['fieldErrors' => ['submissions', 'approvals', 'push', 'quietFrom', 'quietTo']]);
    }

    public function test_mark_all_and_mark_one_read(): void
    {
        $user = $this->makeUser();
        $a = $this->seedNotification($user->id, ['type' => 't1']);
        $b = $this->seedNotification($user->id, ['type' => 't2']);
        Passport::actingAs($user, [], 'api');

        $this->postJson("/api/v2/notifications/{$a->id}/mark-read")->assertNoContent();
        $this->assertSame('Read', $a->fresh()->status);
        $this->assertSame('Not Read', $b->fresh()->status);

        $this->postJson('/api/v2/notifications/mark-all-read')->assertNoContent();
        $this->assertSame('Read', $b->fresh()->status);
    }

    public function test_cannot_mark_other_users_notification(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $n = $this->seedNotification($owner->id);

        Passport::actingAs($other, [], 'api');

        $this->postJson("/api/v2/notifications/{$n->id}/mark-read")
            ->assertStatus(404)->assertJsonPath('code', 'not_found');
    }

    public function test_inbox_requires_auth(): void
    {
        $this->getJson('/api/v2/notifications/inbox?tab=all')->assertStatus(401);
    }

    // --- §11.14.6 / §11.14.7 device-token register / unregister --------------

    public function test_device_token_register_creates_row(): void
    {
        $user = $this->makeUser();
        Passport::actingAs($user, [], 'api');

        $token = str_repeat('f', 64);

        $this->postJson('/api/v2/notifications/device-token', [
            'token' => $token,
            'platform' => 'android',
            'appVersion' => 'v2.4.0',
        ])->assertNoContent();

        $row = DeviceToken::where('token', $token)->first();
        $this->assertNotNull($row);
        $this->assertSame((int) $user->id, (int) $row->user_id);
        $this->assertSame('android', $row->platform);
        $this->assertSame('v2.4.0', $row->app_version);
    }

    public function test_device_token_register_is_idempotent_and_transfers_owner(): void
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $token = str_repeat('a', 64);

        Passport::actingAs($userA, [], 'api');
        $this->postJson('/api/v2/notifications/device-token', ['token' => $token])->assertNoContent();
        $this->postJson('/api/v2/notifications/device-token', ['token' => $token])->assertNoContent();

        // Same token re-registered by a different user → ownership transfers.
        Passport::actingAs($userB, [], 'api');
        $this->postJson('/api/v2/notifications/device-token', ['token' => $token, 'platform' => 'ios'])->assertNoContent();

        $rows = DeviceToken::where('token', $token)->get();
        $this->assertCount(1, $rows, 'token must remain unique across the table');
        $this->assertSame((int) $userB->id, (int) $rows->first()->user_id);
        $this->assertSame('ios', $rows->first()->platform);
    }

    public function test_device_token_register_validation(): void
    {
        Passport::actingAs($this->makeUser(), [], 'api');

        $this->postJson('/api/v2/notifications/device-token', [])
            ->assertStatus(422)->assertJsonStructure(['fieldErrors' => ['token']]);

        $this->postJson('/api/v2/notifications/device-token', ['token' => 'short'])
            ->assertStatus(422)->assertJsonStructure(['fieldErrors' => ['token']]);

        $this->postJson('/api/v2/notifications/device-token', [
            'token' => str_repeat('x', 64), 'platform' => 'symbian',
        ])->assertStatus(422)->assertJsonStructure(['fieldErrors' => ['platform']]);
    }

    public function test_device_token_unregister_only_removes_own_token(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $token = str_repeat('b', 64);
        DeviceToken::create(['user_id' => $owner->id, 'token' => $token, 'platform' => 'android']);

        // Other user trying to unregister → silent no-op (row stays).
        Passport::actingAs($other, [], 'api');
        $this->deleteJson('/api/v2/notifications/device-token', ['token' => $token])->assertNoContent();
        $this->assertNotNull(DeviceToken::where('token', $token)->first());

        // Owner unregistering → row gone.
        Passport::actingAs($owner, [], 'api');
        $this->deleteJson('/api/v2/notifications/device-token', ['token' => $token])->assertNoContent();
        $this->assertNull(DeviceToken::where('token', $token)->first());
    }

    public function test_device_token_endpoints_require_auth(): void
    {
        $this->postJson('/api/v2/notifications/device-token', ['token' => str_repeat('c', 64)])->assertStatus(401);
        $this->deleteJson('/api/v2/notifications/device-token', ['token' => str_repeat('c', 64)])->assertStatus(401);
    }

    // --- dispatcher fan-out --------------------------------------------------

    public function test_dispatcher_writes_inbox_row_and_enqueues_per_device(): void
    {
        Bus::fake([SendFcmJob::class]);
        $fakeTransport = new NullFcmTransport();
        app()->instance(FcmTransport::class, $fakeTransport);

        $recipient = $this->makeUser();
        DeviceToken::create(['user_id' => $recipient->id, 'token' => str_repeat('d', 64), 'platform' => 'android']);
        DeviceToken::create(['user_id' => $recipient->id, 'token' => str_repeat('e', 64), 'platform' => 'ios']);

        $dispatcher = app(NotificationDispatcher::class);
        $dispatcher->dispatch(
            collect([$recipient]),
            NotificationDispatcher::KIND_APPROVAL,
            'Test title',
            'Test body',
            ['senderId' => 1, 'modelId' => 99, 'deepLinkRoute' => 'kpiTrackingDetail', 'deepLinkParams' => ['kpiId' => 'k1']],
        );

        $inbox = Notification::where('user_id', $recipient->id)->first();
        $this->assertNotNull($inbox);
        $this->assertSame('Test title', $inbox->title);
        $this->assertSame('approval', $inbox->type);

        // One job per device token.
        Bus::assertDispatchedTimes(SendFcmJob::class, 2);
    }

    public function test_dispatcher_honors_push_off_preference(): void
    {
        Bus::fake([SendFcmJob::class]);

        $recipient = $this->makeUser();
        NotificationPreference::create([
            'user_id' => $recipient->id,
            'submissions' => true, 'approvals' => true, 'rejections' => true, 'mentions' => true, 'deadlines' => true,
            'push' => false, // push channel off
            'email' => true, 'sms' => false,
        ]);
        DeviceToken::create(['user_id' => $recipient->id, 'token' => str_repeat('f', 64), 'platform' => 'android']);

        app(NotificationDispatcher::class)->dispatch(
            collect([$recipient]),
            NotificationDispatcher::KIND_APPROVAL,
            'Inbox-only',
            'No push',
        );

        // Inbox row still written.
        $this->assertNotNull(Notification::where('user_id', $recipient->id)->first());
        // But NO push job enqueued.
        Bus::assertNotDispatched(SendFcmJob::class);
    }

    public function test_dispatcher_honors_per_kind_preference(): void
    {
        Bus::fake([SendFcmJob::class]);

        $recipient = $this->makeUser();
        NotificationPreference::create([
            'user_id' => $recipient->id,
            'submissions' => true, 'approvals' => false, // approvals off
            'rejections' => true, 'mentions' => true, 'deadlines' => true,
            'push' => true, 'email' => true, 'sms' => false,
        ]);
        DeviceToken::create(['user_id' => $recipient->id, 'token' => str_repeat('g', 64), 'platform' => 'android']);

        app(NotificationDispatcher::class)->dispatch(
            collect([$recipient]),
            NotificationDispatcher::KIND_APPROVAL,
            't', 'b',
        );

        Bus::assertNotDispatched(SendFcmJob::class);
    }

    public function test_sector_head_recipient_resolution(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw);
        $sectorHead = $this->makeSectorHead($sector);
        $commitment = $this->makeCommitment($sector);
        $deliverable = $this->makeDeliverable($commitment);
        $kpi = $this->makeKpi($deliverable);
        $tracking = $this->makeTracking($kpi);
        $tracking->load('kpi.deliverable.commitment');

        $resolved = app(NotificationDispatcher::class)->sectorHeadsForTracking($tracking);

        $this->assertTrue($resolved->pluck('id')->contains((int) $sectorHead->id),
            'Sector Head of the sector should be among recipients');
    }

    // --- unified approvalCopy() across v2 + web --------------------------

    public function test_approval_copy_produces_unified_stage_specific_wording(): void
    {
        $ctx = ['kpiTitle' => 'Maternal Mortality', 'sectorName' => 'Health'];

        [$t1, $b1] = NotificationDispatcher::approvalCopy(NotificationDispatcher::STAGE_SUBMITTED, $ctx);
        $this->assertSame('Submission awaiting your approval', $t1);
        $this->assertStringContainsString('Data Admin submitted "Maternal Mortality" (Health)', $b1);

        [$t2, $b2] = NotificationDispatcher::approvalCopy(NotificationDispatcher::STAGE_SECTOR_HEAD_ACCEPTED, $ctx);
        $this->assertSame('Submission awaiting your verification', $t2);
        $this->assertStringContainsString('Sector Head approved "Maternal Mortality" (Health)', $b2);

        [$t3, $b3] = NotificationDispatcher::approvalCopy(NotificationDispatcher::STAGE_FACILITATOR_ACCEPTED, $ctx);
        $this->assertSame('Submission awaiting final approval', $t3);
        $this->assertStringContainsString('Facilitator verified "Maternal Mortality" (Health)', $b3);

        [$t4, $b4] = NotificationDispatcher::approvalCopy(NotificationDispatcher::STAGE_COORDINATOR_ACCEPTED, $ctx);
        $this->assertSame('Submission confirmed', $t4);
        $this->assertStringContainsString('Coordinator finalised "Maternal Mortality" (Health)', $b4);

        [$t5, $b5] = NotificationDispatcher::approvalCopy(
            NotificationDispatcher::STAGE_REJECTED,
            array_merge($ctx, ['rejectingRole' => 'Sector Head', 'rejectionReason' => 'Missing supporting data']),
        );
        $this->assertSame('Submission needs revision', $t5);
        $this->assertStringContainsString('Sector Head rejected "Maternal Mortality" (Health): Missing supporting data', $b5);
        $this->assertStringContainsString('Review and resubmit', $b5);
    }

    public function test_approval_copy_rejection_omits_reason_when_blank(): void
    {
        [, $body] = NotificationDispatcher::approvalCopy(
            NotificationDispatcher::STAGE_REJECTED,
            ['kpiTitle' => 'X', 'sectorName' => 'Y', 'rejectingRole' => 'Facilitator'],
        );

        $this->assertStringNotContainsString(': ', $body); // no colon separator when no reason
        $this->assertStringContainsString('Facilitator rejected "X" (Y). Review and resubmit.', $body);
    }

    public function test_approval_copy_rejection_truncates_long_reason(): void
    {
        $longReason = str_repeat('really long rejection reason ', 20); // ~580 chars

        [, $body] = NotificationDispatcher::approvalCopy(
            NotificationDispatcher::STAGE_REJECTED,
            ['kpiTitle' => 'X', 'sectorName' => 'Y', 'rejectingRole' => 'Coordinator', 'rejectionReason' => $longReason],
        );

        $this->assertStringContainsString('…', $body, 'overlong reason should be truncated with an ellipsis');
        $this->assertLessThan(280, mb_strlen($body), 'rejected body should stay under push-friendly length');
    }

    // --- web-flow delegation: legacy helpers route through the dispatcher ---

    public function test_legacy_notify_sector_head_for_approval_writes_inbox_via_dispatcher(): void
    {
        Bus::fake([\App\Jobs\SendFcmJob::class]);

        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw);
        $sectorHead = $this->makeSectorHead($sector);
        DeviceToken::create([
            'user_id' => $sectorHead->id,
            'token' => str_repeat('h', 64),
            'platform' => 'android',
        ]);

        $kpi = $this->makeKpi($this->makeDeliverable($this->makeCommitment($sector)));
        $tracking = $this->makeTracking($kpi);
        $tracking->load('kpi.deliverable.commitment');

        // Simulate web context — Auth::user() is the Data Admin submitting.
        $dataAdmin = $this->makeDataAdmin($sector);
        $this->actingAs($dataAdmin);

        \App\Models\Notification::notifySectorHeadForApproval($tracking);

        // Inbox row written with the unified title from approvalCopy().
        $inbox = \App\Models\Notification::where('user_id', $sectorHead->id)->first();
        $this->assertNotNull($inbox);
        $this->assertSame('Submission awaiting your approval', $inbox->title);
        $this->assertSame('submission', $inbox->type);

        // FCM push enqueued for the SH device — the legacy helper now reaches
        // the live HTTP v1 transport via the dispatcher.
        Bus::assertDispatched(\App\Jobs\SendFcmJob::class);
    }

    public function test_legacy_helpers_no_longer_write_self_confirmation_rows(): void
    {
        // Previously each helper wrote two rows: one for the next-role
        // recipient + one back to the actor ("Your submission was sent to …").
        // The actor row is gone in the unified flow.
        Bus::fake([\App\Jobs\SendFcmJob::class]);

        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw);
        $sectorHead = $this->makeSectorHead($sector);
        $dataAdmin = $this->makeDataAdmin($sector);

        $kpi = $this->makeKpi($this->makeDeliverable($this->makeCommitment($sector)));
        $tracking = $this->makeTracking($kpi);
        $tracking->load('kpi.deliverable.commitment');

        $this->actingAs($dataAdmin);
        \App\Models\Notification::notifySectorHeadForApproval($tracking);

        // Sector head gets the inbox row…
        $this->assertSame(1, \App\Models\Notification::where('user_id', $sectorHead->id)->count());
        // …but the actor (Data Admin) gets NO self-confirmation row.
        $this->assertSame(0, \App\Models\Notification::where('user_id', $dataAdmin->id)->count());
    }
}
