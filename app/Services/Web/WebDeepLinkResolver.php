<?php

namespace App\Services\Web;

use App\Models\PerformanceTracking;
use App\Models\User;

/**
 * Translates a mobile `deepLinkRoute` (the route name the dispatcher attaches
 * to every push payload — `kpiReviewSheet`, `kpiTrackingDetail`,
 * `dataEntryWindow`) into the equivalent **web admin** URL.
 *
 * Some mobile routes collapse onto a single screen but the web has
 * role-specific surfaces — `kpiReviewSheet` in particular needs to dispatch
 * the user to the Sector Head review page, the Facilitator queue, or the
 * Coordinator final-review page depending on who they are. The role is read
 * from the supplied User.
 *
 * All resolution is best-effort: unknown routes / missing params / unknown
 * roles fall back to the admin dashboard, never to a 404.
 */
class WebDeepLinkResolver
{
    /**
     * Resolve a mobile deep link to a web URL.
     *
     * @param  array<string,mixed>  $params  decoded `deepLinkParams`
     */
    public function resolve(string $route, array $params, ?User $user): string
    {
        return match ($route) {
            'kpiReviewSheet'    => $this->resolveReviewSheet($user),
            'kpiTrackingDetail' => $this->resolveTrackingDetail($params),
            'dataEntryWindow'   => $this->resolveDataEntryWindow($params),
            default             => $this->fallback(),
        };
    }

    /**
     * Mobile's "review sheet" is a single screen; the web has separate review
     * pages per reviewer role. Route the user to the page they're authorised
     * to act on.
     */
    private function resolveReviewSheet(?User $user): string
    {
        if (! $user) {
            return $this->fallback();
        }

        if ($user->isCoordinator() || $user->isDeputyCoordinator()) {
            return route('delivery.coordinator.final-review');
        }
        if ($user->isFacilitator()) {
            return route('delivery.awaiting.verification');
        }
        if ($user->isSectorHead()) {
            return route('performance.tracking.sector-head-review');
        }

        // Data Admin / other roles — the review sheet doesn't apply; send
        // them to the tracking screen so they can see the row in question.
        return $this->fallback();
    }

    /**
     * `kpiTrackingDetail` carries `{ "kpiId": "<id>" }`. The web route is
     * `commitment/deliverable/kpi/{kpi}/{track}` — needs both kpi id and the
     * specific tracking row id. Resolve the most recent tracking for that
     * kpi; if none, fall back.
     */
    private function resolveTrackingDetail(array $params): string
    {
        $kpiId = (string) ($params['kpiId'] ?? '');
        if ($kpiId === '') {
            return $this->fallback();
        }

        $trackingId = (int) (PerformanceTracking::where('kpi_id', $kpiId)
            ->orderByDesc('id')
            ->value('id') ?? 0);

        return route('performance.tracking', [
            'kpi' => $kpiId,
            'track' => $trackingId,
        ]);
    }

    /**
     * `dataEntryWindow` carries `{ sectorId, year, quarter }`. The web's
     * data-entry page is a single list with optional filters — pass them as
     * query params so the page can pre-select the right sector/period.
     */
    private function resolveDataEntryWindow(array $params): string
    {
        $query = array_filter([
            'sector_id' => $params['sectorId'] ?? null,
            'year' => $params['year'] ?? null,
            'quarter' => isset($params['quarter']) ? str_replace('q', '', (string) $params['quarter']) : null,
        ], fn ($v) => $v !== null && $v !== '');

        return route('data-entry.index', $query);
    }

    /**
     * Safe destination when the route name is unknown, the params are
     * missing, or the user's role isn't one the destination applies to.
     */
    private function fallback(): string
    {
        // `home` is the admin landing route; every authenticated user can
        // land there. If for some reason it's been renamed/removed, fall
        // back to the application root rather than throwing.
        try {
            return route('home');
        } catch (\Throwable) {
            return url('/');
        }
    }
}
