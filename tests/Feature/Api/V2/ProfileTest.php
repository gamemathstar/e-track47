<?php

namespace Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPdcuAuth;

    public function test_profile_me_returns_full_profile(): void
    {
        $user = $this->makeUser([
            'full_name' => 'Amina Egbe',
            'email' => 'amina@pdcu.gov.ng',
            'phone_number' => '+234 803 000 0000',
        ], 'Coordinator');
        Passport::actingAs($user, [], 'api');

        $this->getJson('/api/v2/profile/me')
            ->assertOk()
            ->assertJsonPath('id', (string) $user->id)
            ->assertJsonPath('fullName', 'Amina Egbe')
            ->assertJsonPath('email', 'amina@pdcu.gov.ng')
            ->assertJsonPath('phone', '+234 803 000 0000')
            ->assertJsonPath('role', 'coordinator')
            ->assertJsonPath('organization', 'Jigawa State Government')
            ->assertJsonMissingPath('data');
    }

    public function test_profile_me_requires_authentication(): void
    {
        $this->getJson('/api/v2/profile/me')
            ->assertStatus(401)
            ->assertJsonPath('code', 'unauthenticated');
    }
}
