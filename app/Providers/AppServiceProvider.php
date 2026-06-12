<?php

namespace App\Providers;

use App\Services\V2\Notifications\FcmTransport;
use App\Services\V2\Notifications\KreaitFcmTransport;
use App\Services\V2\Notifications\NullFcmTransport;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once dirname(__DIR__).'/helpers.php';

        // FCM transport binding. Use the real Kreait transport when a
        // service-account JSON is configured AND readable; otherwise fall
        // back to NullFcmTransport so dev / CI / un-provisioned envs don't
        // crash on a notification dispatch.
        $this->app->singleton(FcmTransport::class, function () {
            $credentials = (string) config('services.fcm.credentials', '');
            if ($credentials !== '' && is_file($credentials) && is_readable($credentials)) {
                return new KreaitFcmTransport();
            }
            return new NullFcmTransport();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
