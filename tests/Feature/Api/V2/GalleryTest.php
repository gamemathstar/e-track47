<?php

namespace Tests\Feature\Api\V2;

use App\Models\Gallery;
use App\Models\GalleryComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

/**
 * §11.13 gallery: admin management list + public list + detail + multipart upload.
 */
class GalleryTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPdcuAuth;

    private function seedItem(array $attrs = []): Gallery
    {
        $g = new Gallery();
        $g->title = $attrs['title'] ?? 'Lekki Viaduct';
        $g->caption = $attrs['caption'] ?? 'Phase II works.';
        $g->status = $attrs['status'] ?? 'active';
        $g->display_order = $attrs['display_order'] ?? 1;
        $g->image_path = $attrs['image_path'] ?? 'uploads/galleries/sample.jpg';
        $g->category = $attrs['category'] ?? 'infrastructure';
        $g->is_public = $attrs['is_public'] ?? true;
        $g->uploaded_by = $attrs['uploaded_by'] ?? null;
        $g->save();

        return $g;
    }

    public function test_management_list_requires_tab(): void
    {
        Passport::actingAs($this->makeUser(['target_entity' => 'System'], 'System Admin'), [], 'api');

        $this->getJson('/api/v2/gallery/management')
            ->assertStatus(422)->assertJsonStructure(['fieldErrors' => ['tab']]);
    }

    public function test_management_list_returns_required_shape(): void
    {
        $this->seedItem(['title' => 'A', 'category' => 'infrastructure']);
        $this->seedItem(['title' => 'B', 'category' => 'health', 'status' => 'inactive']);

        Passport::actingAs($this->makeUser(['target_entity' => 'System'], 'System Admin'), [], 'api');

        $res = $this->getJson('/api/v2/gallery/management?tab=all')->assertOk()->assertJsonCount(2);
        $res->assertJsonStructure([['id', 'title', 'category', 'categoryLabel', 'iconKey', 'gradientKeys', 'isActive', 'isPublic', 'displayOrder']]);

        $this->getJson('/api/v2/gallery/management?tab=archived')->assertOk()->assertJsonCount(1)->assertJsonPath('0.title', 'B');
    }

    public function test_public_list_filters_by_category(): void
    {
        $this->seedItem(['title' => 'Road', 'category' => 'infrastructure']);
        $this->seedItem(['title' => 'Hospital', 'category' => 'health']);
        $this->seedItem(['title' => 'Hidden', 'category' => 'infrastructure', 'is_public' => false]);

        Passport::actingAs($this->makeUser(), [], 'api');

        $this->getJson('/api/v2/gallery/public?filter=roads')
            ->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.category', 'infrastructure')
            ->assertJsonPath('0.isPublic', true);

        $this->getJson('/api/v2/gallery/public?filter=healthcare')
            ->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.category', 'health');
    }

    public function test_gallery_detail(): void
    {
        $g = $this->seedItem(['title' => 'Lekki Viaduct']);
        Passport::actingAs($this->makeUser(), [], 'api');

        $this->getJson("/api/v2/gallery/items/{$g->id}")
            ->assertOk()
            ->assertJsonStructure(['id', 'title', 'dateLabel', 'descriptionBlocks', 'isVerified', 'verifiedPillLabel', 'heroIconKey', 'heroGradientKeys', 'stats', 'comments'])
            ->assertJsonPath('title', 'Lekki Viaduct');
    }

    public function test_upload_creates_gallery_item(): void
    {
        Storage::fake('public');
        Passport::actingAs($this->makeUser(['target_entity' => 'System'], 'System Admin'), [], 'api');

        $this->post('/api/v2/gallery/items', [
            'title' => 'New Bridge', 'description' => 'Opened today.',
            'category' => 'infrastructure', 'displayOrder' => 0, 'isPublic' => 'true',
            'asset' => UploadedFile::fake()->image('bridge.jpg'),
        ], ['Accept' => 'application/json'])->assertStatus(202);

        $g = Gallery::where('title', 'New Bridge')->first();
        $this->assertNotNull($g);
        $this->assertStringStartsWith('uploads/galleries/', $g->image_path);
    }

    public function test_upload_requires_admin(): void
    {
        Passport::actingAs($this->makeUser(), [], 'api');

        $this->postJson('/api/v2/gallery/items', [
            'title' => 'X', 'description' => 'X', 'category' => 'health', 'displayOrder' => 0, 'isPublic' => true,
        ])->assertStatus(403)->assertJsonPath('code', 'forbidden');
    }

    public function test_management_requires_admin(): void
    {
        Passport::actingAs($this->makeUser(), [], 'api');

        $this->getJson('/api/v2/gallery/management?tab=all')->assertStatus(403);
    }

    public function test_unknown_item_is_404(): void
    {
        Passport::actingAs($this->makeUser(), [], 'api');

        $this->getJson('/api/v2/gallery/items/999999')->assertStatus(404)->assertJsonPath('code', 'not_found');
    }

    // --- public-capable access (no Authorization header) -------------------

    public function test_public_list_works_without_authorization_header(): void
    {
        $this->seedItem(['title' => 'Road', 'category' => 'infrastructure']);
        $this->seedItem(['title' => 'Hidden', 'category' => 'infrastructure', 'is_public' => false]);

        // No Passport::actingAs — signed-out caller, no bearer.
        $this->getJson('/api/v2/gallery/public?filter=roads')
            ->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Road');
    }

    public function test_public_detail_works_without_authorization_header(): void
    {
        $g = $this->seedItem(['title' => 'Public Bridge', 'is_public' => true]);

        $this->getJson("/api/v2/gallery/items/{$g->id}")
            ->assertOk()
            ->assertJsonPath('title', 'Public Bridge');
    }

    public function test_private_item_returns_404_to_anonymous(): void
    {
        $private = $this->seedItem(['title' => 'Internal Only', 'is_public' => false]);

        // No existence leak — 404, not 403.
        $this->getJson("/api/v2/gallery/items/{$private->id}")
            ->assertStatus(404)->assertJsonPath('code', 'not_found');
    }

    public function test_archived_item_returns_404_to_anonymous_even_if_marked_public(): void
    {
        $archived = $this->seedItem(['title' => 'Old', 'status' => 'inactive', 'is_public' => true]);

        $this->getJson("/api/v2/gallery/items/{$archived->id}")
            ->assertStatus(404)->assertJsonPath('code', 'not_found');
    }

    public function test_system_admin_can_view_private_or_archived_item(): void
    {
        $private = $this->seedItem(['title' => 'Internal Only', 'is_public' => false]);
        $archived = $this->seedItem(['title' => 'Old', 'status' => 'inactive', 'is_public' => true]);

        Passport::actingAs($this->makeUser(['target_entity' => 'System'], 'System Admin'), [], 'api');

        $this->getJson("/api/v2/gallery/items/{$private->id}")->assertOk()->assertJsonPath('title', 'Internal Only');
        $this->getJson("/api/v2/gallery/items/{$archived->id}")->assertOk()->assertJsonPath('title', 'Old');
    }

    public function test_management_still_requires_bearer(): void
    {
        // Auth-required endpoints stay 401 for anonymous — only public reads
        // were opened up.
        $this->getJson('/api/v2/gallery/management?tab=all')->assertStatus(401);
        $this->postJson('/api/v2/gallery/items', [
            'title' => 'X', 'description' => 'X', 'category' => 'health',
            'displayOrder' => 0, 'isPublic' => true,
        ])->assertStatus(401);
    }

    // --- §11.13.5 unauthenticated public comment submit -----------------------

    public function test_public_comment_submit_returns_204_and_holds_for_moderation(): void
    {
        $g = $this->seedItem(['is_public' => true, 'status' => 'active']);

        // No Passport::actingAs — anonymous caller.
        $this->postJson("/api/v2/gallery/items/{$g->id}/comments", [
            'authorName' => 'Aminu Danladi',
            'body' => 'Glad to see this is moving forward — phase 2 timeline?',
        ])->assertNoContent();   // 204 (or 202, both acceptable per contract)

        $row = GalleryComment::where('gallery_id', $g->id)->first();
        $this->assertNotNull($row);
        $this->assertSame('Aminu Danladi', $row->commenter_name);
        $this->assertSame('Glad to see this is moving forward — phase 2 timeline?', $row->comment);
        $this->assertSame('pending', $row->status);
        // phone_number / email left blank — the v2 contract doesn't carry them.
        $this->assertNull($row->phone_number);
        $this->assertNull($row->email);
    }

    public function test_public_comment_does_not_echo_the_created_row(): void
    {
        $g = $this->seedItem(['is_public' => true]);

        // 204 No Content — explicitly empty body.
        $response = $this->postJson("/api/v2/gallery/items/{$g->id}/comments", [
            'authorName' => 'Bola', 'body' => 'Nice.',
        ])->assertNoContent();

        $this->assertSame('', $response->getContent(), 'created row must not be echoed');
    }

    public function test_pending_comment_is_NOT_visible_on_public_detail(): void
    {
        $g = $this->seedItem(['is_public' => true]);
        // Pre-create an approved comment so the comments[] array has shape.
        GalleryComment::create([
            'gallery_id' => $g->id, 'commenter_name' => 'Approved A',
            'comment' => 'Earlier approved comment.', 'status' => 'approved',
        ]);

        // Anonymous user submits — held for moderation.
        $this->postJson("/api/v2/gallery/items/{$g->id}/comments", [
            'authorName' => 'Pending P', 'body' => 'New unapproved comment.',
        ])->assertNoContent();

        $res = $this->getJson("/api/v2/gallery/items/{$g->id}")->assertOk();
        $names = collect($res->json('comments'))->pluck('authorName')->all();

        $this->assertContains('Approved A', $names);
        $this->assertNotContains('Pending P', $names,
            'pending comments must not surface on the public detail');
    }

    public function test_public_comment_submit_validation(): void
    {
        $g = $this->seedItem(['is_public' => true]);

        $this->postJson("/api/v2/gallery/items/{$g->id}/comments", [])
            ->assertStatus(422)
            ->assertJsonStructure(['fieldErrors' => ['authorName', 'body']]);

        $this->postJson("/api/v2/gallery/items/{$g->id}/comments", [
            'authorName' => '', 'body' => 'hi',
        ])->assertStatus(422)->assertJsonStructure(['fieldErrors' => ['authorName']]);

        $this->postJson("/api/v2/gallery/items/{$g->id}/comments", [
            'authorName' => str_repeat('a', 121), 'body' => 'hi',
        ])->assertStatus(422)->assertJsonStructure(['fieldErrors' => ['authorName']]);

        $this->postJson("/api/v2/gallery/items/{$g->id}/comments", [
            'authorName' => 'X', 'body' => str_repeat('z', 2001),
        ])->assertStatus(422)->assertJsonStructure(['fieldErrors' => ['body']]);
    }

    public function test_cannot_submit_comment_on_private_or_archived_or_missing_item(): void
    {
        $private = $this->seedItem(['is_public' => false]);
        $archived = $this->seedItem(['is_public' => true, 'status' => 'inactive']);

        // 404 — uniform with the detail-read existence-check (no info leak).
        foreach ([$private->id, $archived->id, 999999] as $id) {
            $this->postJson("/api/v2/gallery/items/{$id}/comments", [
                'authorName' => 'X', 'body' => 'y',
            ])->assertStatus(404)->assertJsonPath('code', 'not_found');

            $this->assertSame(0, GalleryComment::where('gallery_id', $id)->count());
        }
    }
}
