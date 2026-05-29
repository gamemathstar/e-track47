<?php

namespace Tests\Feature\Api\V2;

use App\Models\Notification;
use App\Models\NotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
