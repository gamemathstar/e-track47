<?php

namespace Tests\Feature\Api\V2;

use App\Models\ApiRefreshToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Passport\Passport;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPdcuAuth;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPersonalAccessClient();
        // Avoid cross-test throttle accumulation on the login limiter.
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_login_returns_raw_session_with_wire_role(): void
    {
        $this->makeUser(['email' => 'amina@pdcu.gov.ng', 'password' => 'secret123'], 'Coordinator');

        $response = $this->postJson('/api/v2/auth/login', [
            'email' => 'amina@pdcu.gov.ng',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'refresh_token', 'user' => ['id', 'email', 'name', 'role', 'mustChangePassword']])
            ->assertJsonPath('user.email', 'amina@pdcu.gov.ng')
            ->assertJsonPath('user.role', 'coordinator')
            ->assertJsonPath('user.mustChangePassword', false)
            ->assertJsonMissingPath('data');

        $this->assertIsString($response->json('access_token'));
        $this->assertIsString($response->json('refresh_token'));
    }

    public function test_login_role_is_null_when_unassigned(): void
    {
        $this->makeUser(['email' => 'norole@pdcu.gov.ng', 'password' => 'secret123']); // no role

        $this->postJson('/api/v2/auth/login', ['email' => 'norole@pdcu.gov.ng', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('user.role', null);
    }

    public function test_login_rejects_bad_credentials_with_401(): void
    {
        $this->makeUser(['email' => 'amina@pdcu.gov.ng', 'password' => 'secret123']);

        $this->postJson('/api/v2/auth/login', ['email' => 'amina@pdcu.gov.ng', 'password' => 'wrong-password'])
            ->assertStatus(401)
            ->assertJsonPath('code', 'invalid_credentials');
    }

    public function test_login_validation_errors_use_field_errors_contract(): void
    {
        $this->postJson('/api/v2/auth/login', [])
            ->assertStatus(422)
            ->assertJsonStructure(['code', 'message', 'fieldErrors' => ['email', 'password']])
            ->assertJsonPath('code', 'validation_error');
    }

    public function test_me_returns_current_user(): void
    {
        $user = $this->makeUser(['email' => 'me@pdcu.gov.ng'], 'Facilitator');
        Passport::actingAs($user, [], 'api');

        $this->getJson('/api/v2/auth/me')
            ->assertOk()
            ->assertJsonPath('id', (string) $user->id)
            ->assertJsonPath('role', 'facilitator');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v2/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('code', 'unauthenticated');
    }

    public function test_force_password_change_clears_flag_and_returns_204(): void
    {
        $user = $this->makeUser(['email' => 'force@pdcu.gov.ng', 'password' => 'secret123', 'must_change_password' => true]);
        Passport::actingAs($user, [], 'api');

        $this->postJson('/api/v2/auth/password/force-change', ['newPassword' => 'brandnew123'])
            ->assertNoContent();

        $this->assertFalse((bool) $user->fresh()->must_change_password);
    }

    public function test_refresh_rotates_and_invalidates_old_token(): void
    {
        $this->makeUser(['email' => 'refresh@pdcu.gov.ng', 'password' => 'secret123']);

        $login = $this->postJson('/api/v2/auth/login', ['email' => 'refresh@pdcu.gov.ng', 'password' => 'secret123'])->json();

        $refreshed = $this->postJson('/api/v2/auth/refresh', ['refresh_token' => $login['refresh_token']]);
        $refreshed->assertOk()->assertJsonStructure(['access_token', 'refresh_token']);

        // The original refresh token is now revoked (single-use rotation).
        $this->postJson('/api/v2/auth/refresh', ['refresh_token' => $login['refresh_token']])
            ->assertStatus(401)
            ->assertJsonPath('code', 'invalid_refresh_token');
    }

    public function test_logout_revokes_refresh_tokens_and_returns_204(): void
    {
        $user = $this->makeUser(['email' => 'logout@pdcu.gov.ng', 'password' => 'secret123']);
        // Seed an active refresh token for the user.
        ApiRefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => ApiRefreshToken::hashToken('raw-token'),
            'expires_at' => now()->addDays(30),
        ]);
        Passport::actingAs($user, [], 'api');

        $this->postJson('/api/v2/auth/logout')->assertNoContent();

        $this->assertSame(0, ApiRefreshToken::where('user_id', $user->id)->whereNull('revoked_at')->count());
    }
}
