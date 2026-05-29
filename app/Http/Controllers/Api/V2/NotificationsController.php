<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\V2\Notifications\UpdateNotificationPreferencesRequest;
use App\Services\V2\NotificationsService;
use Illuminate\Http\Request;

/**
 * Notifications (API_REFERENCE.md §11.14): user inbox + per-user preferences.
 */
class NotificationsController extends BaseController
{
    public function __construct(private readonly NotificationsService $notifications)
    {
    }

    public function inbox(Request $request): array
    {
        $validated = $request->validate(['tab' => ['required', 'in:all,unread,mentions']]);

        return $this->notifications->inbox($request->user(), $validated['tab']);
    }

    public function preferences(Request $request): array
    {
        return $this->notifications->getPreferences($request->user());
    }

    public function updatePreferences(UpdateNotificationPreferencesRequest $request)
    {
        $this->notifications->updatePreferences($request->user(), $request->validated());

        return $this->noContent();
    }

    public function markAllRead(Request $request)
    {
        $this->notifications->markAllRead($request->user());

        return $this->noContent();
    }

    public function markRead(Request $request, string $id)
    {
        $this->notifications->markRead($request->user(), $id);

        return $this->noContent();
    }
}
