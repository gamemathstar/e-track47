<?php

namespace Tests\Feature\Web;

use App\Models\Notification;
use App\Models\Sector;
use App\Services\Web\WebDeepLinkResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

/**
 * Web-facing notifications surface — inbox list, follow (tap-through), and
 * mark-all-read. Symmetric with the mobile v2 inbox (§11.14) but routed
 * through Blade + session auth.
 */
class NotificationsWebTest extends TestCase
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
        if (array_key_exists('deep_link_route', $attrs)) {
            $n->deep_link_route = $attrs['deep_link_route'];
        }
        if (array_key_exists('deep_link_params', $attrs)) {
            $n->deep_link_params = is_array($attrs['deep_link_params'])
                ? json_encode($attrs['deep_link_params'])
                : $attrs['deep_link_params'];
        }
        $n->save();

        return $n;
    }

    // --- /notifications (index) ----------------------------------------------

    public function test_index_requires_auth(): void
    {
        $this->get('/notifications')->assertRedirect('/login');
    }

    public function test_index_renders_grouped_inbox_for_authenticated_user(): void
    {
        $user = $this->makeUser();
        $this->seedNotification($user->id, ['title' => 'New submission', 'type' => 'submission', 'kind' => 'submission']);
        $this->seedNotification($user->id, ['title' => 'Old approval', 'status' => 'Read', 'kind' => 'approval']);

        $this->actingAs($user);

        $res = $this->get('/notifications')->assertOk();
        $res->assertSee('Notifications');
        $res->assertSee('New submission');
        $res->assertSee('Old approval');
        $res->assertSee('Today'); // section label
    }

    public function test_index_tab_filter_scopes_results(): void
    {
        $user = $this->makeUser();
        $this->seedNotification($user->id, ['title' => 'UNIQUE_UNREAD_FOR_TEST']);
        $this->seedNotification($user->id, ['title' => 'UNIQUE_READ_FOR_TEST', 'status' => 'Read']);

        $this->actingAs($user);

        // The unread tab still renders the unread item in the page body.
        $this->get('/notifications?tab=unread')->assertOk()
            ->assertSee('UNIQUE_UNREAD_FOR_TEST');

        // The all tab shows both.
        $all = $this->get('/notifications?tab=all')->assertOk();
        $all->assertSee('UNIQUE_UNREAD_FOR_TEST');
        $all->assertSee('UNIQUE_READ_FOR_TEST');

        // NOTE: we deliberately don't assertDontSee('Read item') on the unread
        // tab — the topbar dropdown (composed via TopbarNotificationsComposer)
        // always shows the 5 most recent regardless of the active tab. That
        // would put the read item in the response body even when the main
        // content is correctly filtered.
    }

    public function test_other_users_notifications_are_not_shown(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $this->seedNotification($owner->id, ['title' => 'Belongs to owner']);
        $this->seedNotification($other->id, ['title' => 'Belongs to other']);

        $this->actingAs($owner);

        $res = $this->get('/notifications')->assertOk();
        $res->assertSee('Belongs to owner');
        $res->assertDontSee('Belongs to other');
    }

    // --- /notifications/{id}/follow -----------------------------------------

    public function test_follow_marks_read_and_redirects_to_deep_link(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw);
        $sh = $this->makeSectorHead($sector);

        // Sector head receives an action-required submission ping.
        $n = $this->seedNotification($sh->id, [
            'type' => 'submission',
            'kind' => 'submission',
            'deep_link_route' => 'kpiReviewSheet',
            'deep_link_params' => ['kpiId' => '42'],
        ]);

        $this->actingAs($sh);

        // Sector Head's review sheet → performance.tracking.sector-head-review
        $this->get("/notifications/{$n->id}/follow")
            ->assertRedirect(route('performance.tracking.sector-head-review'));

        $this->assertSame('Read', $n->fresh()->status);
    }

    public function test_follow_routes_facilitator_to_their_queue(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw);
        $fac = $this->makeFacilitator($sector);

        $n = $this->seedNotification($fac->id, [
            'deep_link_route' => 'kpiReviewSheet',
            'deep_link_params' => ['kpiId' => '7'],
        ]);

        $this->actingAs($fac);

        $this->get("/notifications/{$n->id}/follow")
            ->assertRedirect(route('delivery.awaiting.verification'));
    }

    public function test_follow_routes_coordinator_to_final_review(): void
    {
        $coord = $this->makeUser([], 'Coordinator');
        $n = $this->seedNotification($coord->id, [
            'deep_link_route' => 'kpiReviewSheet',
            'deep_link_params' => ['kpiId' => '7'],
        ]);

        $this->actingAs($coord);

        $this->get("/notifications/{$n->id}/follow")
            ->assertRedirect(route('delivery.coordinator.final-review'));
    }

    public function test_follow_unknown_route_falls_back_to_home(): void
    {
        $user = $this->makeUser();
        $n = $this->seedNotification($user->id, [
            'deep_link_route' => 'someRouteWeDoNotKnow',
            'deep_link_params' => [],
        ]);

        $this->actingAs($user);

        $this->get("/notifications/{$n->id}/follow")
            ->assertRedirect(route('home'));
    }

    public function test_cannot_follow_someone_elses_notification(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $n = $this->seedNotification($owner->id);

        $this->actingAs($other);

        $this->get("/notifications/{$n->id}/follow")->assertStatus(404);
    }

    // --- /notifications/mark-all-read ---------------------------------------

    public function test_mark_all_read_flips_all_unread_rows(): void
    {
        $user = $this->makeUser();
        $a = $this->seedNotification($user->id);
        $b = $this->seedNotification($user->id);

        $this->actingAs($user);

        $this->post('/notifications/mark-all-read')->assertRedirect();

        $this->assertSame('Read', $a->fresh()->status);
        $this->assertSame('Read', $b->fresh()->status);
    }

    // --- WebDeepLinkResolver unit-level checks ------------------------------

    public function test_resolver_data_entry_window_passes_query_params(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw);
        $coord = $this->makeUser([], 'Coordinator');

        $resolver = app(WebDeepLinkResolver::class);
        $url = $resolver->resolve('dataEntryWindow', [
            'sectorId' => (string) $sector->id,
            'year' => '2024',
            'quarter' => 'q3',
        ], $coord);

        $this->assertStringContainsString('sector_id='.$sector->id, $url);
        $this->assertStringContainsString('year=2024', $url);
        $this->assertStringContainsString('quarter=3', $url, 'quarter "q3" should be normalised to "3"');
    }

    public function test_resolver_tracking_detail_uses_latest_tracking_row(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw);
        $commitment = $this->makeCommitment($sector);
        $deliverable = $this->makeDeliverable($commitment);
        $kpi = $this->makeKpi($deliverable);

        // Two tracking rows — resolver should pick the most recent (highest id).
        $this->makeTracking($kpi, ['quarter' => 1]);
        $latest = $this->makeTracking($kpi, ['quarter' => 2]);

        $resolver = app(WebDeepLinkResolver::class);
        $url = $resolver->resolve('kpiTrackingDetail', ['kpiId' => (string) $kpi->id], $this->makeUser());

        $expected = route('performance.tracking', ['kpi' => $kpi->id, 'track' => $latest->id]);
        $this->assertSame($expected, $url);
    }
}
