<?php

namespace App\Services\V2;

use App\Exceptions\V2\ApiException;
use App\Models\ApiRefreshToken;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Settings, Help & About (API_REFERENCE.md §11.12). Per-user preferences;
 * stateless command endpoints (clear-cache/sync/sign-out-all); seeded FAQs +
 * institutional About. Feedback is multipart and persisted as a flat record on
 * disk for now (no dedicated table — deliberate scope choice for this phase).
 */
class SettingsService
{
    public function getPreferences(User $user): array
    {
        return $this->serialize($this->loadOrDefault($user));
    }

    public function updatePreferences(User $user, array $data): void
    {
        $row = $this->loadOrDefault($user);
        $row->fill([
            'theme_mode' => $data['themeMode'],
            'font_scale' => (float) $data['fontScale'],
            'biometric_enabled' => (bool) $data['biometricEnabled'],
            'cellular_uploads_enabled' => (bool) $data['cellularUploadsEnabled'],
            'sync_on_wifi_only' => (bool) $data['syncOnWifiOnly'],
            'language_code' => $data['languageCode'],
            'language_label' => $data['languageLabel'],
        ]);
        $row->save();
    }

    /** Stateless command: server-side cache nuke would land here later. */
    public function clearCache(User $user): void
    {
        // no-op for now (the mobile contract only needs a 2xx).
    }

    public function sync(User $user): void
    {
        // no-op for now.
    }

    /** Revoke every Passport access token + every refresh token for the user. */
    public function signOutAll(User $user): void
    {
        if (Schema::hasTable('oauth_access_tokens')) {
            DB::table('oauth_access_tokens')->where('user_id', $user->id)->update(['revoked' => true]);
        }
        ApiRefreshToken::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    /** @return array<int,array> */
    public function faqs(): array
    {
        return [
            ['id' => 'faq-submit-kpi', 'question' => 'How do I submit a KPI?',
                'answer' => 'Open the deliverable, tap the KPI, fill in the actual value with any evidence, then Submit for review.'],
            ['id' => 'faq-resubmit', 'question' => 'A reviewer rejected my submission — how do I resubmit?',
                'answer' => 'Open the KPI, address the reviewer\'s remarks, and resubmit. The workflow restarts from the Sector Head.'],
            ['id' => 'faq-data-entry-window', 'question' => 'Why can\'t I enter data for my sector?',
                'answer' => 'Data entry windows are managed by the Coordinator. If your window is locked, ask for an override.'],
            ['id' => 'faq-reports', 'question' => 'Where do I download the quarterly report?',
                'answer' => 'Reports → Comprehensive → Generate. The download link appears once the file is ready.'],
            ['id' => 'faq-roles', 'question' => 'How are roles assigned?',
                'answer' => 'The System Admin assigns roles. Switch roles via your profile if you hold more than one.'],
        ];
    }

    public function submitFeedback(User $user, string $subject, string $message, ?UploadedFile $screenshot): void
    {
        $payload = [
            'subject' => $subject,
            'message' => $message,
            'user_id' => $user->id,
            'submitted_at' => now()->toIso8601String(),
        ];
        if ($screenshot) {
            $ext = $screenshot->getClientOriginalExtension() ?: 'png';
            $name = 'feedback_'.$user->id.'_'.time().'.'.$ext;
            Storage::disk('public')->putFileAs('uploads/feedback', $screenshot, $name);
            $payload['screenshot'] = 'uploads/feedback/'.$name;
        }

        Storage::disk('local')->append(
            'feedback.log',
            json_encode($payload, JSON_UNESCAPED_SLASHES).PHP_EOL
        );
    }

    public function about(): array
    {
        return [
            'heroTitle' => 'About PDCU',
            'heroSubtitle' => 'Performance Delivery & Coordination Unit',
            'mission' => 'PDCU coordinates and accelerates delivery of the State\'s annual performance framework across all MDAs.',
            'contacts' => [
                ['iconKey' => 'email', 'label' => 'Email Address', 'value' => 'info@pdcu.gov.ng', 'kind' => 'email'],
                ['iconKey' => 'call', 'label' => 'Hotline', 'value' => '+234 800 000 0000', 'kind' => 'phone'],
            ],
            'socials' => [
                ['id' => 'linkedin', 'label' => 'LinkedIn', 'iconKey' => 'brand_awareness', 'url' => 'https://linkedin.com/company/pdcu'],
                ['id' => 'x', 'label' => 'X (Twitter)', 'iconKey' => 'public', 'url' => 'https://x.com/pdcu'],
            ],
            'statusLabel' => 'System Status: Operational',
            'versionLabel' => 'App Version v2.0.0',
            'copyrightLabel' => '© '.date('Y').' PDCU. All rights reserved.',
            'isOperational' => true,
        ];
    }

    // --- helpers -------------------------------------------------------------

    private function loadOrDefault(User $user): UserSetting
    {
        // Refresh to pull DB defaults into the new instance.
        return UserSetting::firstOrCreate(['user_id' => $user->id])->refresh();
    }

    private function serialize(UserSetting $s): array
    {
        return [
            'themeMode' => $s->theme_mode,
            'fontScale' => round((float) $s->font_scale, 2),
            'biometricEnabled' => (bool) $s->biometric_enabled,
            'cellularUploadsEnabled' => (bool) $s->cellular_uploads_enabled,
            'syncOnWifiOnly' => (bool) $s->sync_on_wifi_only,
            'languageCode' => $s->language_code,
            'languageLabel' => $s->language_label,
            'appVersion' => 'v2.0.0',
        ];
    }
}
