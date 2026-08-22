<?php

namespace Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

class SystemTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPdcuAuth;

    public function test_status_is_public_capable(): void
    {
        // No token: still returns 200 (auth.optional middleware).
        $this->getJson('/api/v2/system/status')
            ->assertOk()
            ->assertJsonStructure(['mode', 'title', 'body', 'etaLabel', 'rotatingStatus', 'sessionId'])
            ->assertJsonPath('mode', 'normal');
    }

    public function test_update_is_public_capable_and_shapes_correctly(): void
    {
        $this->getJson('/api/v2/system/update')
            ->assertOk()
            ->assertJsonStructure(['currentVersion', 'requiredVersion', 'title', 'body', 'releaseNotesUrl'])
            ->assertJsonPath('currentVersion', 'v2.0.0');
    }

    public function test_onboarding_slides_public_and_three_slides(): void
    {
        $this->getJson('/api/v2/system/onboarding')
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonStructure([['id', 'iconKey', 'title', 'body']]);
    }

    public function test_offline_snapshot_requires_auth(): void
    {
        $this->getJson('/api/v2/system/offline-snapshot')->assertStatus(401);

        Passport::actingAs($this->makeUser(), [], 'api');
        $this->getJson('/api/v2/system/offline-snapshot')
            ->assertOk()
            ->assertJsonStructure(['title', 'body', 'systemVersionLabel', 'cachedCards' => [['id', 'label', 'value', 'iconKey', 'accent']]]);
    }

    public function test_retry_and_onboarding_complete_204(): void
    {
        Passport::actingAs($this->makeUser(), [], 'api');

        $this->postJson('/api/v2/system/retry')->assertNoContent();
        $this->postJson('/api/v2/system/onboarding/complete')->assertNoContent();
    }
}
