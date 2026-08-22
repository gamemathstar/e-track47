<?php

namespace App\Providers;

use App\Services\V2\Notifications\FcmTransport;
use App\Services\V2\Notifications\KreaitFcmTransport;
use App\Services\V2\Notifications\NullFcmTransport;
use App\View\Composers\TopbarNotificationsComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once dirname(__DIR__).'/helpers.php';

        // FCM transport binding. Use the real Kreait HTTP v1 transport when
        // Firebase credentials are configured (via FIREBASE_CREDENTIALS file
        // path OR FIREBASE_CREDENTIALS_JSON inline JSON — see
        // config/firebase.php); otherwise fall back to NullFcmTransport so
        // dev / CI / un-provisioned envs don't crash on a notification
        // dispatch.
        $this->app->singleton(FcmTransport::class, function () {
            return $this->firebaseCredentialsAvailable()
                ? new KreaitFcmTransport()
                : new NullFcmTransport();
        });
    }

    private function firebaseCredentialsAvailable(): bool
    {
        $project = (string) config('firebase.default', 'app');
        $credentials = config("firebase.projects.{$project}.credentials");

        // Inline JSON (FIREBASE_CREDENTIALS_JSON) — kreait's config resolves
        // it to a decoded array. Treat any non-empty array as "configured".
        if (is_array($credentials)) {
            return ! empty($credentials);
        }

        // File path (FIREBASE_CREDENTIALS / GOOGLE_APPLICATION_CREDENTIALS).
        if (is_string($credentials) && $credentials !== '') {
            return is_file($credentials) && is_readable($credentials);
        }

        return false;
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Make the topbar notification bell data available on every render
        // of the topbar partial — no controller boilerplate needed.
        View::composer('commons.menu.topbar', TopbarNotificationsComposer::class);
    }
}
