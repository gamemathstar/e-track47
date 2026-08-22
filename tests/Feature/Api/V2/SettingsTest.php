<?php

namespace Tests\Feature\Api\V2;

use App\Models\ApiRefreshToken;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\Concerns\InteractsWithPdcuAuth;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPdcuAuth;

    public function test_preferences_defaults_then_update(): void
    {
        $user = $this->makeUser();
        Passport::actingAs($user, [], 'api');

        $defaults = $this->getJson('/api/v2/settings/preferences')->assertOk()->json();
        $this->assertSame('system', $defaults['themeMode']);
        $this->assertSame('en-NG', $defaults['languageCode']);
        $this->assertTrue($defaults['syncOnWifiOnly']);

        $this->putJson('/api/v2/settings/preferences', [
            'themeMode' => 'dark', 'fontScale' => 0.6,
            'biometricEnabled' => true, 'cellularUploadsEnabled' => true, 'syncOnWifiOnly' => false,
            'languageCode' => 'en-NG', 'languageLabel' => 'English (Nigeria)',
            'appVersion' => 'v2.0.0',
        ])->assertNoContent();

        $row = UserSetting::where('user_id', $user->id)->first();
        $this->assertSame('dark', $row->theme_mode);
        $this->assertSame(0.6, (float) $row->font_scale);
        $this->assertFalse((bool) $row->sync_on_wifi_only);
    }

    public function test_preferences_update_validation(): void
    {
        Passport::actingAs($this->makeUser(), [], 'api');

        $this->putJson('/api/v2/settings/preferences', ['themeMode' => 'rainbow'])
            ->assertStatus(422)
            ->assertJsonStructure(['fieldErrors' => ['themeMode']]);
    }

    public function test_clear_cache_sync_and_sign_out_all(): void
    {
        $user = $this->makeUser();
        ApiRefreshToken::create(['user_id' => $user->id, 'token_hash' => ApiRefreshToken::hashToken('x'), 'expires_at' => now()->addDays(30)]);
        Passport::actingAs($user, [], 'api');

        $this->postJson('/api/v2/settings/clear-cache')->assertNoContent();
        $this->postJson('/api/v2/settings/sync')->assertNoContent();
        $this->postJson('/api/v2/settings/sign-out-all')->assertNoContent();

        $this->assertSame(0, ApiRefreshToken::where('user_id', $user->id)->whereNull('revoked_at')->count());
    }

    public function test_faqs_and_about(): void
    {
        Passport::actingAs($this->makeUser(), [], 'api');

        $this->getJson('/api/v2/settings/faqs')->assertOk()
            ->assertJsonStructure([['id', 'question', 'answer']]);

        $this->getJson('/api/v2/settings/about')->assertOk()
            ->assertJsonStructure(['heroTitle', 'heroSubtitle', 'mission', 'contacts' => [['iconKey', 'label', 'value', 'kind']], 'socials' => [['id', 'label', 'iconKey', 'url']], 'statusLabel', 'versionLabel', 'copyrightLabel', 'isOperational']);
    }

    public function test_feedback_multipart_with_and_without_screenshot(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Passport::actingAs($this->makeUser(), [], 'api');

        $this->postJson('/api/v2/settings/feedback', ['subject' => 'Bug', 'message' => 'It crashes.'])
            ->assertStatus(202);

        $this->post('/api/v2/settings/feedback', [
            'subject' => 'Bug2', 'message' => 'Now with screenshot.',
            'screenshot' => UploadedFile::fake()->image('shot.png'),
        ], ['Accept' => 'application/json'])->assertStatus(202);
    }

    public function test_feedback_validation(): void
    {
        Passport::actingAs($this->makeUser(), [], 'api');
        $this->postJson('/api/v2/settings/feedback', [])
            ->assertStatus(422)->assertJsonStructure(['fieldErrors' => ['subject', 'message']]);
    }

    public function test_settings_requires_auth(): void
    {
        $this->getJson('/api/v2/settings/preferences')->assertStatus(401);
    }
}
