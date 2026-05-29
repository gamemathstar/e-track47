<?php

namespace App\Http\Controllers\Api\V2;

use App\Services\V2\SystemService;
use Illuminate\Http\Request;

/**
 * System signals (API_REFERENCE.md §11.10). status/update/onboarding are
 * public-capable (use `auth.optional` middleware) so the splash/gate can show
 * them pre-login (A6). offline-snapshot/retry/onboarding-complete are bearer.
 */
class SystemController extends BaseController
{
    public function __construct(private readonly SystemService $system)
    {
    }

    public function status(Request $request): array
    {
        return $this->system->status();
    }

    public function update(Request $request): array
    {
        return $this->system->update();
    }

    public function offlineSnapshot(Request $request): array
    {
        return $this->system->offlineSnapshot();
    }

    public function retry(Request $request)
    {
        return $this->noContent();
    }

    public function onboardingSlides(Request $request): array
    {
        return $this->system->onboardingSlides();
    }

    public function completeOnboarding(Request $request)
    {
        $this->system->completeOnboarding();

        return $this->noContent();
    }
}
