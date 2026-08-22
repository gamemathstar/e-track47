<?php

namespace App\Services\V2;

/**
 * System signals (API_REFERENCE.md §11.10): status / update / offline /
 * onboarding. Currently config/static payloads — easy to flip to a
 * `system_settings` table later without contract change.
 */
class SystemService
{
    public function status(): array
    {
        $mode = (string) config('apiv2.system.mode', 'normal');
        $rotating = (array) config('apiv2.system.rotating_status', []);

        return [
            'mode' => $mode,
            'title' => (string) config('apiv2.system.title', ''),
            'body' => (string) config('apiv2.system.body', ''),
            'etaLabel' => (string) config('apiv2.system.eta_label', ''),
            'rotatingStatus' => array_values(array_map('strval', $rotating)),
            'sessionId' => 'Session ID: '.substr(md5((string) request()?->ip().date('Ymd')), 0, 12),
        ];
    }

    public function update(): array
    {
        return [
            'currentVersion' => (string) config('apiv2.update.current', 'v2.0.0'),
            'requiredVersion' => (string) config('apiv2.update.required', 'v2.0.0'),
            'title' => (string) config('apiv2.update.title', 'You are on the latest version'),
            'body' => (string) config('apiv2.update.body', ''),
            'releaseNotesUrl' => (string) config('apiv2.update.release_notes_url', 'https://pdcu.gov.ng/release-notes'),
        ];
    }

    public function offlineSnapshot(): array
    {
        return [
            'title' => 'Connectivity Lost',
            'body' => 'You appear to be offline. Cached content is available while you reconnect.',
            'systemVersionLabel' => 'System Version: v2.0.0-OFFLINE',
            'cachedCards' => [
                ['id' => 'last-viewed', 'label' => 'Last viewed', 'value' => 'Health Sector', 'iconKey' => 'history', 'accent' => 'secondary', 'pillLabel' => 'LOCAL'],
                ['id' => 'unsynced', 'label' => 'Unsynced drafts', 'value' => '0 unsynced reports', 'iconKey' => 'pending_actions', 'accent' => 'error'],
            ],
        ];
    }

    public function onboardingSlides(): array
    {
        return [
            ['id' => 'step-1', 'iconKey' => 'monitoring', 'title' => 'Track MDA Progress',
                'body' => 'Monitor delivery across all sectors in real time, with quarterly performance updates.',
                'pillIconKey' => 'trending_up', 'pillLabel' => 'LIVE PROGRESS', 'pillValue' => '87.4%'],
            ['id' => 'step-2', 'iconKey' => 'verified', 'title' => 'Streamline Approvals',
                'body' => 'Move submissions through the sector head → facilitator → coordinator workflow with a tap.',
                'pillIconKey' => 'task_alt', 'pillLabel' => 'WORKFLOW READY'],
            ['id' => 'step-3', 'iconKey' => 'bar_chart', 'title' => 'Generate Reports',
                'body' => 'Produce comprehensive Excel, Word, and PDF reports on demand.'],
        ];
    }

    public function completeOnboarding(): void
    {
        // No-op for now; persisted per-user once `user_settings` learns an onboarded flag.
    }
}
