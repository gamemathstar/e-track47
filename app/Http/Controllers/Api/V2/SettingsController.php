<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\V2\Settings\FeedbackRequest;
use App\Http\Requests\V2\Settings\UpdateSettingsPreferencesRequest;
use App\Services\V2\SettingsService;
use Illuminate\Http\Request;

/**
 * Settings, Help & About (API_REFERENCE.md §11.12).
 */
class SettingsController extends BaseController
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function preferences(Request $request): array
    {
        return $this->settings->getPreferences($request->user());
    }

    public function updatePreferences(UpdateSettingsPreferencesRequest $request)
    {
        $this->settings->updatePreferences($request->user(), $request->validated());

        return $this->noContent();
    }

    public function clearCache(Request $request)
    {
        $this->settings->clearCache($request->user());

        return $this->noContent();
    }

    public function sync(Request $request)
    {
        $this->settings->sync($request->user());

        return $this->noContent();
    }

    public function signOutAll(Request $request)
    {
        $this->settings->signOutAll($request->user());

        return $this->noContent();
    }

    public function faqs(Request $request): array
    {
        return $this->settings->faqs();
    }

    public function feedback(FeedbackRequest $request)
    {
        $data = $request->validated();
        $this->settings->submitFeedback($request->user(), $data['subject'], $data['message'], $request->file('screenshot'));

        return $this->accepted();
    }

    public function about(Request $request): array
    {
        return $this->settings->about();
    }
}
