<?php

namespace Tests\Feature\Api\V2;

use App\Models\DiscussionComment;
use App\Models\DiscussionCommentLike;
use App\Models\DiscussionThread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

class DiscussionsTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPdcuAuth;

    private function seedThread(): array
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => 'Health']);

        $thread = DiscussionThread::create([
            'sector_id' => $sector->id,
            'title' => 'Digital Infrastructure Rollout',
            'status' => 'in_progress',
            'status_label' => 'In Progress',
            'lead_name' => 'Dr. Adebayo Omotola',
            'lead_label' => 'LEAD OFFICER',
            'lead_initials' => 'AO',
            'author_name' => 'Aminu Danladi',
            'preview_body' => 'Fibre backbone phase 1 is ahead of schedule.',
        ]);

        return [$sector, $thread];
    }

    public function test_hub_returns_required_shape(): void
    {
        [$sector] = $this->seedThread();
        Passport::actingAs($this->makeUser(), [], 'api');

        $this->getJson('/api/v2/discussions/hub?filter=all')
            ->assertOk()
            ->assertJsonStructure([
                'sectors' => [['id', 'name', 'tagline', 'accent', 'iconKey', 'countLabel']],
                'trending' => ['hotTopicTag', 'hotTopicTitle', 'hotTopicBody', 'healthBody', 'educationBody'],
            ])
            ->assertJsonPath('sectors.0.name', 'Health');
    }

    public function test_hub_requires_filter(): void
    {
        Passport::actingAs($this->makeUser(), [], 'api');
        $this->getJson('/api/v2/discussions/hub')->assertStatus(422)->assertJsonStructure(['fieldErrors' => ['filter']]);
    }

    public function test_sector_thread_feed(): void
    {
        [$sector, $thread] = $this->seedThread();
        Passport::actingAs($this->makeUser(), [], 'api');

        $this->getJson("/api/v2/discussions/sectors/{$sector->id}/threads?tab=commitments")
            ->assertOk()->assertJsonCount(1)
            ->assertJsonStructure([['threadId', 'title', 'commentCountLabel', 'authorName', 'timeLabel', 'previewBody']])
            ->assertJsonPath('0.threadId', (string) $thread->id);
    }

    public function test_unknown_sector_threads_404(): void
    {
        Passport::actingAs($this->makeUser(), [], 'api');
        $this->getJson('/api/v2/discussions/sectors/999999/threads?tab=commitments')
            ->assertStatus(404)->assertJsonPath('code', 'not_found');
    }

    public function test_thread_detail_and_post_comment_and_reply(): void
    {
        [$sector, $thread] = $this->seedThread();
        $user = $this->makeUser(['full_name' => 'Chinelo J.']);
        Passport::actingAs($user, [], 'api');

        // Top-level comment.
        $this->postJson("/api/v2/discussions/threads/{$thread->id}/comments", ['body' => 'Phase 1 fibre is live.'])
            ->assertStatus(202);
        $top = DiscussionComment::where('thread_id', $thread->id)->whereNull('parent_id')->first();
        $this->assertNotNull($top);

        // Reply.
        $this->postJson("/api/v2/discussions/threads/{$thread->id}/comments", [
            'body' => 'Great — what is the SLA?',
            'parentId' => (string) $top->id,
        ])->assertStatus(202);

        $detail = $this->getJson("/api/v2/discussions/threads/{$thread->id}")->assertOk()->json();
        $this->assertSame((string) $thread->id, $detail['id']);
        $this->assertSame('in_progress', $detail['status']);
        $this->assertCount(2, $detail['comments']);
        // Reply carries parentId.
        $reply = collect($detail['comments'])->first(fn ($c) => isset($c['parentId']));
        $this->assertNotNull($reply);
        $this->assertSame((string) $top->id, $reply['parentId']);
    }

    public function test_post_comment_validation(): void
    {
        [$sector, $thread] = $this->seedThread();
        Passport::actingAs($this->makeUser(), [], 'api');

        $this->postJson("/api/v2/discussions/threads/{$thread->id}/comments", [])
            ->assertStatus(422)->assertJsonStructure(['fieldErrors' => ['body']]);
    }

    public function test_post_to_unknown_thread_404(): void
    {
        Passport::actingAs($this->makeUser(), [], 'api');
        $this->postJson('/api/v2/discussions/threads/999999/comments', ['body' => 'x'])
            ->assertStatus(404)->assertJsonPath('code', 'not_found');
    }

    public function test_reply_with_wrong_parent_is_422(): void
    {
        [$sector, $thread] = $this->seedThread();
        $other = DiscussionThread::create(['sector_id' => $sector->id, 'title' => 'Other', 'status' => 'in_progress']);
        $orphan = DiscussionComment::create(['thread_id' => $other->id, 'author_name' => 'X', 'body' => 'X']);

        Passport::actingAs($this->makeUser(), [], 'api');
        $this->postJson("/api/v2/discussions/threads/{$thread->id}/comments", ['body' => 'x', 'parentId' => (string) $orphan->id])
            ->assertStatus(422)->assertJsonStructure(['fieldErrors' => ['parentId']]);
    }

    public function test_toggle_like_idempotent(): void
    {
        [$sector, $thread] = $this->seedThread();
        $c = DiscussionComment::create(['thread_id' => $thread->id, 'author_name' => 'X', 'body' => 'X']);
        $user = $this->makeUser();
        Passport::actingAs($user, [], 'api');

        $this->postJson("/api/v2/discussions/comments/{$c->id}/toggle-like")->assertStatus(202);
        $this->assertSame(1, (int) $c->fresh()->like_count);
        $this->assertSame(1, DiscussionCommentLike::where('comment_id', $c->id)->where('user_id', $user->id)->count());

        // Toggle off.
        $this->postJson("/api/v2/discussions/comments/{$c->id}/toggle-like")->assertStatus(202);
        $this->assertSame(0, (int) $c->fresh()->like_count);
    }

    public function test_thread_detail_marks_my_likes(): void
    {
        [$sector, $thread] = $this->seedThread();
        $c = DiscussionComment::create(['thread_id' => $thread->id, 'author_name' => 'X', 'body' => 'X']);
        $user = $this->makeUser();
        Passport::actingAs($user, [], 'api');

        $this->postJson("/api/v2/discussions/comments/{$c->id}/toggle-like")->assertStatus(202);

        $detail = $this->getJson("/api/v2/discussions/threads/{$thread->id}")->assertOk()->json();
        $this->assertTrue($detail['comments'][0]['isLikedByCurrentUser']);
        $this->assertSame(1, $detail['comments'][0]['likeCount']);
    }

    public function test_unknown_comment_like_404(): void
    {
        Passport::actingAs($this->makeUser(), [], 'api');
        $this->postJson('/api/v2/discussions/comments/999999/toggle-like')
            ->assertStatus(404)->assertJsonPath('code', 'not_found');
    }

    public function test_endpoints_require_auth(): void
    {
        $this->getJson('/api/v2/discussions/hub?filter=all')->assertStatus(401);
    }
}
