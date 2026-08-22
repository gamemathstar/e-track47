<?php

namespace App\Http\Controllers;

use App\Exceptions\V2\ApiException;
use App\Services\Web\WebDeepLinkResolver;
use App\Services\V2\NotificationsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Web-facing notifications surface for admin-panel users (Sector Heads,
 * Facilitators, Coordinators, Data Admins logging in via the browser).
 *
 * Symmetric with the v2 mobile inbox (§11.14) — same underlying notifications
 * table, same NotificationsService, same recipient resolution — but renders
 * through Blade and translates mobile route names into the equivalent web
 * URLs via WebDeepLinkResolver.
 */
class NotificationsController extends Controller
{
    public function __construct(
        private readonly NotificationsService $service,
        private readonly WebDeepLinkResolver $linkResolver,
    ) {
        $this->middleware('auth');
    }

    /**
     * Full notifications page — list grouped by date with a tab filter.
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'all');
        if (! in_array($tab, ['all', 'unread', 'mentions'], true)) {
            $tab = 'all';
        }

        $payload = $this->service->inbox(Auth::user(), $tab);

        // Layer in the resolved web URL per item so the blade can render
        // a single anchor without needing to know about the resolver.
        $payload['sections'] = collect($payload['sections'])->map(function (array $section) {
            $section['notifications'] = collect($section['notifications'])->map(function (array $item) {
                $item['followUrl'] = route('notifications.follow', ['id' => $item['id']]);
                return $item;
            })->all();
            return $section;
        })->all();

        return view('pages.notifications.index', [
            'payload' => $payload,
            'activeTab' => $tab,
        ]);
    }

    /**
     * Tap-through handler: marks the notification read, resolves the deep
     * link to a web URL, and redirects. One entry point so the bell dropdown
     * and the index page can share the same href.
     */
    public function follow(Request $request, string $id)
    {
        // The shared NotificationsService throws ApiException::notFound on
        // unknown / not-yours rows — that's perfect for /api/v2 routes but
        // turns into a 500 on the web stack. Translate to a 404 abort.
        try {
            $notification = $this->service->markReadAndReturn(Auth::user(), $id);
        } catch (ApiException $e) {
            abort($e->getStatus());
        }

        $url = $this->linkResolver->resolve(
            (string) ($notification->deep_link_route ?? ''),
            $this->coerceParams($notification->deep_link_params),
            Auth::user(),
        );

        return redirect()->to($url);
    }

    public function markAllRead(Request $request)
    {
        $this->service->markAllRead(Auth::user());

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Normalise the deep_link_params column (stored as a JSON string) into
     * a flat string→scalar array the resolver can consume.
     *
     * @return array<string,mixed>
     */
    private function coerceParams($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }
}
