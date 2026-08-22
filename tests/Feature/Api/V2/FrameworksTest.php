<?php

namespace Tests\Feature\Api\V2;

use App\Models\Framework;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

/**
 * §11.5 framework lifecycle: list, stats, detail, sectors, create, archive,
 * set-default. Coordinator-only for mutations (else 403).
 */
class FrameworksTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPdcuAuth;

    public function test_list_and_stats_and_detail_and_sectors(): void
    {
        $fw = $this->makeFramework();
        $sector = $this->makeSector($fw, ['sector_name' => 'Health']);
        $this->makeCommitment($sector);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson('/api/v2/frameworks')
            ->assertOk()->assertJsonCount(1)
            ->assertJsonStructure([['id', 'title', 'subtitle', 'status', 'statusLabel', 'sectorCountLabel', 'dateLabel']])
            ->assertJsonPath('0.status', 'active');

        $this->getJson('/api/v2/frameworks/stats')
            ->assertOk()
            ->assertJsonStructure(['activeCount', 'archivedCount', 'latestUpdateLabel', 'latestUpdateValue'])
            ->assertJsonPath('activeCount', 1);

        $this->getJson("/api/v2/frameworks/{$fw->id}")
            ->assertOk()
            ->assertJsonPath('id', (string) $fw->id)
            ->assertJsonPath('isDefault', true)
            ->assertJsonStructure(['description', 'reportingYear', 'sectorCount', 'kpiCount']);

        $this->getJson("/api/v2/frameworks/{$fw->id}/sectors")
            ->assertOk()->assertJsonCount(1)
            ->assertJsonStructure([['id', 'frameworkId', 'name', 'meta', 'accent']])
            ->assertJsonPath('0.name', 'Health');
    }

    public function test_create_archives_others_and_returns_new_framework(): void
    {
        $existing = $this->makeFramework(); // year 2024, active
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $res = $this->postJson('/api/v2/frameworks', [
            'name' => 'FY 2025 Framework',
            'sectorMethod' => 'blank',
            'reportingYear' => 2025,
            'description' => 'Plan for 2025',
        ])->assertStatus(201);

        $newId = (int) $res->json('id');
        $this->assertNotSame((int) $existing->id, $newId);
        $this->assertSame('Active', Framework::find($newId)->status);
        $this->assertSame('Archived', $existing->fresh()->status);
    }

    public function test_inherit_copies_sectors_and_children(): void
    {
        $source = $this->makeFramework();
        $sector = $this->makeSector($source, ['sector_name' => 'Health']);
        $commitment = $this->makeCommitment($sector);
        $this->makeDeliverable($commitment);

        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $res = $this->postJson('/api/v2/frameworks', [
            'name' => 'FY 2025', 'sectorMethod' => 'inherit', 'reportingYear' => 2025,
            'inheritedFromFrameworkId' => (string) $source->id,
        ])->assertStatus(201);
        $newId = (int) $res->json('id');

        $this->assertSame(1, Framework::find($newId)->sectors()->count());
        $this->assertSame(1, Framework::find($newId)->commitments()->count());
        $this->assertSame(1, Framework::find($newId)->deliverables()->count());
    }

    public function test_create_duplicate_year_conflicts(): void
    {
        $this->makeFramework(); // 2024 exists
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson('/api/v2/frameworks', ['name' => 'Dup', 'sectorMethod' => 'blank', 'reportingYear' => 2024])
            ->assertStatus(409)->assertJsonPath('code', 'conflict');
    }

    public function test_archive_and_set_default(): void
    {
        $a = $this->makeFramework(); // 2024 active
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        // Create a second one (becomes active, a → archived).
        $res = $this->postJson('/api/v2/frameworks', ['name' => 'FY 2025', 'sectorMethod' => 'blank', 'reportingYear' => 2025])->assertStatus(201);
        $bId = (int) $res->json('id');

        // Set the older one as default again.
        $this->postJson("/api/v2/frameworks/{$a->id}/set-default")->assertStatus(202);
        $this->assertSame('Active', $a->fresh()->status);
        $this->assertSame('Archived', Framework::find($bId)->status);

        // Archive the now-archived 2025 — already archived → 409.
        $this->postJson("/api/v2/frameworks/{$bId}/archive")->assertStatus(409);
    }

    public function test_mutations_require_coordinator(): void
    {
        $this->makeFramework();
        Passport::actingAs($this->makeUser([], 'Sector Head'), [], 'api');

        $this->postJson('/api/v2/frameworks', ['name' => 'x', 'sectorMethod' => 'blank'])
            ->assertStatus(403)->assertJsonPath('code', 'forbidden');
    }

    public function test_create_validation(): void
    {
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->postJson('/api/v2/frameworks', [])
            ->assertStatus(422)->assertJsonStructure(['fieldErrors' => ['name', 'sectorMethod']]);
    }

    public function test_unknown_framework_is_404(): void
    {
        Passport::actingAs($this->makeUser([], 'Coordinator'), [], 'api');

        $this->getJson('/api/v2/frameworks/999999')->assertStatus(404)->assertJsonPath('code', 'not_found');
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v2/frameworks')->assertStatus(401);
    }
}
