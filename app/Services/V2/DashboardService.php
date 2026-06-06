<?php

namespace App\Services\V2;

use App\Exceptions\V2\ApiException;
use App\Models\Commitment;
use App\Models\Framework;
use App\Models\Gallery;
use App\Models\Kpi;
use App\Models\PerformanceTracking;
use App\Models\Sector;
use App\Models\User;
use App\Support\V2\Presenters\SectorPresenter;
use App\Support\V2\WireEnums;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Role dashboards (API_REFERENCE.md §11.11). Each method returns the role-specific
 * snapshot as a raw associative array (computed aggregates, returned as-is — same
 * convention as §11.6). Every endpoint is gated to its role (403 otherwise).
 *
 * Real counts/averages are computed from the data; a few ops/vanity metrics with
 * no DB source (server health, API latency, storage, avg response days, review
 * accuracy) use clearly-marked deterministic placeholders (B3) so the contract's
 * typed fields are always present.
 */
class DashboardService
{
    public function __construct(private readonly HierarchyMetrics $metrics)
    {
    }

    // --- governor ------------------------------------------------------------

    public function governor(User $user): array
    {
        $this->assert($user->isGovernor(), 'governor');

        $fw = $this->activeFramework();
        $sectors = $this->frameworkSectors($fw);
        $sectorMetrics = $this->metrics->forSectors($sectors->pluck('id')->all());
        $rows = $this->sectorPerformanceRows($sectors, $sectorMetrics);

        $kpiFractions = $this->fractionByKpi($this->frameworkKpiIds($fw));
        $buckets = $this->kpiStatusBuckets($kpiFractions, count($this->frameworkKpiIds($fw)));
        $overall = $rows->avg('actualPercent') ?? 0.0;
        $top = $rows->sortByDesc('actualPercent')->first();

        return [
            'greeting' => $this->greeting('Your Excellency.'),
            'greetingDate' => now()->format('l, F j, Y'),
            'overallPercent' => round((float) $overall, 1),
            'overallDeltaLabel' => '+0.0%', // placeholder: no historical baseline stored
            'topPerformerName' => $top['name'] ?? '—',
            'topPerformerPercent' => (float) ($top['actualPercent'] ?? 0),
            'topPerformerKpiCount' => $top ? (int) $this->kpiCountForSector($top['sectorId']) : 0,
            'pendingVerifications' => (int) $this->pendingVerifications($fw),
            'totalKpis' => (int) count($this->frameworkKpiIds($fw)),
            'onTrackCount' => (int) $buckets['on_track'],
            'atRiskCount' => (int) $buckets['at_risk'],
            'delayedCount' => (int) $buckets['delayed'],
            'sectorComparison' => $rows->values()->all(),
            'topInsights' => $rows->sortByDesc('actualPercent')->take(3)->values()->all(),
            'bottomInsights' => $rows->sortBy('actualPercent')->take(3)->values()->all(),
        ];
    }

    // --- coordinator ---------------------------------------------------------

    public function coordinator(User $user): array
    {
        $this->assert($user->isCoordinator() || $user->isDeputyCoordinator(), 'coordinator');

        $fw = $this->activeFramework();
        $kpiIds = $this->frameworkKpiIds($fw);
        // Submission rate is anchored to the framework's reporting year, not
        // the calendar quarter — a KPI is considered submitted if it has any
        // actual_value entered for the framework year (across all quarters).
        $submitted = PerformanceTracking::whereIn('kpi_id', $kpiIds)
            ->where('year', $this->frameworkYear($fw))
            ->whereNotNull('actual_value')->where('actual_value', '!=', '')
            ->distinct('kpi_id')->count('kpi_id');
        $rate = count($kpiIds) > 0 ? round($submitted / count($kpiIds) * 100, 1) : 0.0;

        return [
            'greeting' => $this->greeting('Coordinator.'),
            // Derive from WHO columns rather than confirmation_status so a
            // facilitator-accepted row whose status didn't get updated still
            // counts. Same pattern as the facilitator dashboard.
            'reviewQueueCount' => (int) ApprovalService::applyCoordinatorAwaitingScope(
                PerformanceTracking::query()->whereIn('kpi_id', $kpiIds),
            )->count(),
            'dataEntryOpenSectors' => (int) $this->openDataEntrySectors(),
            'submissionRatePercent' => (float) $rate,
            'submissionRateTarget' => 95.0,
            'frameworkBadgeLabel' => $fw ? 'FRAMEWORK ACTIVE' : 'NO ACTIVE FRAMEWORK',
            'frameworkTitle' => $fw->title ?? '—',
            'recentSubmissions' => $this->recentActivity($kpiIds, 5),
        ];
    }

    // --- facilitator ---------------------------------------------------------

    public function facilitator(User $user): array
    {
        $this->assert((bool) $user->isFacilitator(), 'facilitator');

        $sectorIds = $user->getAssignedSectorIds() ?: [];
        $kpiIds = $this->kpiIdsForSectors($sectorIds);

        // "Awaiting" is derived from the WHO columns (sector_head_approved_by,
        // facilitator_confirmed_by, coordinator_confirmed_by) rather than the
        // confirmation_status string — keeps mobile in sync with the web's
        // /delivery/tracking/awaiting view even when the web's older sector-head
        // approval flow doesn't update confirmation_status. See
        // ApprovalService::applyFacilitatorAwaitingScope for the exact clause.
        $sectorQueues = Sector::whereIn('id', $sectorIds)->orderBy('sector_name')->get()->map(function (Sector $s) use ($user) {
            $kids = $this->kpiIdsForSectors([$s->id]);
            $lastReviewed = PerformanceTracking::whereIn('kpi_id', $kids)->whereNotNull('facilitator_confirmed_at')->max('facilitator_confirmed_at');

            return [
                'sectorId' => (string) $s->id,
                'name' => $s->sector_name,
                'iconKey' => SectorPresenter::icon($s),
                'lastReviewedLabel' => $lastReviewed ? Carbon::parse($lastReviewed)->diffForHumans(['short' => true]) : 'No reviews yet',
                'awaitingCount' => (int) ApprovalService::applyFacilitatorAwaitingScope(
                    PerformanceTracking::query()->whereIn('kpi_id', $kids),
                    $user,
                )->count(),
            ];
        })->values()->all();

        return [
            'awaitingReviewCount' => (int) ApprovalService::applyFacilitatorAwaitingScope(
                PerformanceTracking::query()->whereIn('kpi_id', $kpiIds),
                $user,
            )->count(),
            'sectorQueues' => $sectorQueues,
            'recentDecisions' => $this->recentDecisions($user, 5),
            'avgResponseDays' => 1.4,        // placeholder: no review-timing telemetry stored
            'reviewAccuracyPercent' => 98.0, // placeholder
        ];
    }

    // --- sector head ---------------------------------------------------------

    public function sectorHead(User $user): array
    {
        $sector = $user->isSectorHead();
        $this->assert((bool) $sector, 'sector head');

        $m = $this->metrics->forSectors([$sector->id])[$sector->id] ?? null;
        $commitments = Commitment::where('sector_id', $sector->id)->orderBy('name')->get();
        $commitmentMetrics = $this->metrics->forCommitments($commitments->pluck('id')->all());

        $rows = $commitments->map(function (Commitment $c) use ($commitmentMetrics) {
            $progress = ($commitmentMetrics[$c->id]['progress'] ?? 0.0) * 100;

            return [
                'sectorId' => (string) $c->id,
                'name' => $c->name,
                'iconKey' => 'inventory_2',
                'accent' => SectorPresenter::accent($c->id),
                'actualPercent' => round((float) $progress, 1),
                'planPercent' => (float) $this->planPercent(),
            ];
        })->values()->all();

        return [
            'sectorName' => 'My Sector — '.$sector->sector_name,
            'overallPercent' => $this->pct($m['progress'] ?? 0.0),
            'activeKpis' => (int) $this->kpiCountForSector($sector->id),
            'totalCommitments' => (int) ($m['total'] ?? 0),
            'completedCommitments' => (int) ($m['completed'] ?? 0),
            'inProgressCommitments' => (int) ($m['in_progress'] ?? 0),
            'atRiskCommitments' => (int) ($m['at_risk'] ?? 0),
            // WHO-derived: a row submitted by data admin but stuck at
            // 'Not Confirmed' is still pending the sector head — match the
            // queue endpoint (/approvals/sector-head/queue) so the dashboard
            // and the queue agree on the count.
            'pendingApprovals' => (int) ApprovalService::applySectorHeadAwaitingScope(
                PerformanceTracking::query()
                    ->whereHas('kpi.deliverable.commitment', fn ($q) => $q->where('sector_id', $sector->id))
            )->count(),
            'commitments' => $rows,
        ];
    }

    // --- data admin ----------------------------------------------------------

    /**
     * @param  string|null  $quarterWire  q1–q4 from the client; falls back to
     *                                    the current calendar quarter when
     *                                    omitted, so legacy clients don't break.
     */
    public function dataAdmin(User $user, ?string $quarterWire = null): array
    {
        $sector = $user->isDataAdmin();
        $this->assert((bool) $sector, 'data admin');

        // Resolve the (year, quarter) up front so the KPI filter can scope by
        // them. Year = Active framework's reporting year; quarter is
        // client-supplied (falls back to the calendar quarter when omitted).
        $year = $this->frameworkYear();
        $quarter = $quarterWire ? WireEnums::wireToQuarter($quarterWire) : $this->currentQuarter();

        // Inclusion rule: KPI must have a target row AND a performance_tracking
        // row scoped to *this* quarter/year with a non-empty milestone. Without
        // a milestone the KPI is unconfigured for the period — the data admin
        // has nothing to enter against.
        $kpis = Kpi::query()
            ->whereHas('deliverable.commitment', fn ($q) => $q->where('sector_id', $sector->id))
            ->whereHas('kpiTargets')
            ->whereHas('performanceTracking', function ($q) use ($quarter, $year) {
                $q->where('quarter', $quarter)
                    ->where('year', $year)
                    ->whereNotNull('milestone')
                    ->where('milestone', '<>', '');
            })
            ->with('performanceTracking')
            ->orderBy('kpi')
            ->get();

        $hasSubmission = fn (Kpi $k) => $k->performanceTracking->contains(
            fn ($t) => (int) $t->year === $year && $t->actual_value !== null && $t->actual_value !== ''
        );

        $completed = $kpis->filter($hasSubmission)->count();
        ['label' => $dueLabel, 'accent' => $dueAccent] =
            $this->dataEntryDueLabel((int) $sector->id, $year, $quarter);
        $periodLabel = 'Q'.$quarter.' '.$year;

        $deadlines = $kpis->reject($hasSubmission)->take(2)->map(fn (Kpi $k) => [
            'id' => (string) $k->id,
            'title' => $k->kpi,
            'dueLabel' => $dueLabel,
            'periodLabel' => $periodLabel,
            'ctaLabel' => 'Enter Actual',
            'accent' => $dueAccent,
        ])->values()->all();

        return [
            'sectorName' => $sector->sector_name,
            'quarterLabel' => 'FY '.$year,
            'completedKpis' => (int) $completed,
            'totalKpis' => (int) $kpis->count(),
            'completionPercent' => $kpis->count() > 0 ? round($completed / $kpis->count() * 100, 1) : 0.0,
            'deadlines' => $deadlines,
            'recentActivity' => $this->recentActivity($kpis->pluck('id')->all(), 2),
        ];
    }

    // --- system admin --------------------------------------------------------

    public function systemAdmin(User $user): array
    {
        $this->assert($user->isSystemAdmin(), 'system admin');

        $totalUsers = User::count();
        $activeUsers = User::whereHas('roles', fn ($q) => $q->where('role_status', 'Active'))->count();

        return [
            'totalUsers' => (int) $totalUsers,
            'activeUsers' => (int) $activeUsers,
            'revokedUsers' => (int) max($totalUsers - $activeUsers, 0),
            'userActivePercent' => $totalUsers > 0 ? round($activeUsers / $totalUsers * 100, 1) : 0.0,
            'loginCount24h' => (int) $this->logins24h(),
            'galleryImageCount' => (int) Gallery::count(),
            'activeFrameworkCount' => (int) Framework::where('status', 'Active')->count(),
            'serverHealthPercent' => 100.0,   // placeholder: no ops telemetry in DB
            'apiResponseLabel' => '—',         // placeholder
            'storageLabel' => '—',             // placeholder
            'securityRows' => $this->securityRows(5),
        ];
    }

    // --- shared helpers ------------------------------------------------------

    private function assert(mixed $ok, string $role): void
    {
        if (! $ok) {
            throw ApiException::forbidden("This dashboard is only available to the {$role} role.");
        }
    }

    private function activeFramework(): ?Framework
    {
        return Framework::where('status', 'Active')->first();
    }

    /**
     * Reporting year the dashboards filter against. By design every dashboard
     * surfaces the Active framework's data — NOT the calendar year. Falls back
     * to the calendar year only when no framework is currently Active (which
     * is an edge case; the system should always have one).
     */
    private function frameworkYear(?Framework $fw = null): int
    {
        $fw ??= $this->activeFramework();

        return (int) ($fw->year ?? date('Y'));
    }

    /** @return \Illuminate\Support\Collection<int,Sector> */
    private function frameworkSectors(?Framework $fw)
    {
        return $fw ? Sector::where('framework_id', $fw->id)->orderBy('sector_name')->get() : collect();
    }

    private function frameworkKpiIds(?Framework $fw): array
    {
        return $fw ? Kpi::where('framework_id', $fw->id)->pluck('id')->all() : [];
    }

    private function kpiIdsForSectors(array $sectorIds): array
    {
        if (empty($sectorIds)) {
            return [];
        }

        return Kpi::whereHas('deliverable.commitment', fn ($q) => $q->whereIn('sector_id', $sectorIds))->pluck('id')->all();
    }

    private function kpiCountForSector(int|string $sectorId): int
    {
        return Kpi::whereHas('deliverable.commitment', fn ($q) => $q->where('sector_id', $sectorId))->count();
    }

    private function sectorPending(int|string $sectorId, string $state): int
    {
        return PerformanceTracking::whereHas('kpi.deliverable.commitment', fn ($q) => $q->where('sector_id', $sectorId))
            ->where('confirmation_status', $state)->count();
    }

    /**
     * Build the wire label for the Data Admin's "deadline" rows, derived from
     * the sector's data_entry_accesses row for (year, quarter):
     *
     *   - Today within deadline_date          → "Due {date}", primary accent
     *   - deadline_date past, override set    → "Extended to {date}", tertiary
     *   - deadline_date past, no override     → "Deadline passed", error accent
     *   - No row / table missing              → falls back to "Due this period"
     *
     * @return array{label: string, accent: string}
     */
    private function dataEntryDueLabel(int $sectorId, int $year, int $quarter): array
    {
        $fallback = ['label' => 'Due this period', 'accent' => 'primary'];

        if (! Schema::hasTable('data_entry_accesses')) {
            return $fallback;
        }

        $access = DB::table('data_entry_accesses')
            ->where('sector_id', $sectorId)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->first();

        if (! $access) {
            return $fallback;
        }

        $today = Carbon::today();
        $deadline = $access->deadline_date ? Carbon::parse($access->deadline_date) : null;
        $override = $access->override_deadline ? Carbon::parse($access->override_deadline) : null;

        if ($deadline && $today->lte($deadline)) {
            return ['label' => 'Due '.$deadline->format('M j'), 'accent' => 'primary'];
        }

        if ($override) {
            return ['label' => 'Extended to '.$override->format('M j'), 'accent' => 'tertiary'];
        }

        return ['label' => 'Deadline passed', 'accent' => 'error'];
    }

    /**
     * "Anything still in the pipeline" — used by the Governor dashboard. Derived
     * from WHO columns so rows with stale confirmation_status still count: a
     * row is pending if the data admin has submitted AND the coordinator
     * hasn't finalised it. Facilitator-rejected-and-waiting-resubmit rows are
     * also still pending (they will re-enter the queue once the admin acts).
     */
    private function pendingVerifications(?Framework $fw): int
    {
        return PerformanceTracking::whereIn('kpi_id', $this->frameworkKpiIds($fw))
            ->whereNotNull('actual_value')
            ->where('actual_value', '<>', '')
            ->whereNull('coordinator_confirmed_by')
            ->count();
    }

    private function openDataEntrySectors(): int
    {
        if (! Schema::hasTable('data_entry_accesses')) {
            return 0;
        }

        // Counts distinct sectors with any open/override window in the Active
        // framework's reporting year — across all quarters. Matches the
        // dashboard's framework-period convention rather than the calendar.
        return (int) DB::table('data_entry_accesses')
            ->where('year', $this->frameworkYear())
            ->whereIn('status', ['open', 'override'])
            ->distinct('sector_id')->count('sector_id');
    }

    /** @return \Illuminate\Support\Collection<int,array> */
    private function sectorPerformanceRows($sectors, array $sectorMetrics)
    {
        return $sectors->map(function (Sector $s) use ($sectorMetrics) {
            $actual = ($sectorMetrics[$s->id]['progress'] ?? 0.0) * 100;
            $plan = $this->planPercent();

            return [
                'sectorId' => (string) $s->id,
                'name' => $s->sector_name,
                'iconKey' => SectorPresenter::icon($s),
                'accent' => SectorPresenter::accent($s->id),
                'actualPercent' => round((float) $actual, 1),
                'planPercent' => (float) $plan,
                'deltaLabel' => sprintf('%+.1f', $actual - $plan),
            ];
        })->values();
    }

    private function fractionByKpi(array $kpiIds): array
    {
        if (empty($kpiIds)) {
            return [];
        }

        $value = "NULLIF(COALESCE(NULLIF(delivery_department_value, ''), actual_value), '')";
        $milestone = "NULLIF(milestone, '')";

        return DB::table('performance_trackings')
            ->whereIn('kpi_id', $kpiIds)
            ->selectRaw("kpi_id, AVG(LEAST(CAST({$value} AS DECIMAL(20,4)) / CAST({$milestone} AS DECIMAL(20,4)), 1.0)) AS frac")
            ->groupBy('kpi_id')
            ->pluck('frac', 'kpi_id')
            ->map(fn ($f) => $f === null ? 0.0 : (float) $f)
            ->all();
    }

    private function kpiStatusBuckets(array $fractionByKpi, int $totalKpis): array
    {
        $onTrack = $atRisk = $delayed = 0;
        $withData = 0;
        foreach ($fractionByKpi as $f) {
            $withData++;
            if ($f >= 0.7) {
                $onTrack++;
            } elseif ($f >= 0.4) {
                $atRisk++;
            } else {
                $delayed++;
            }
        }
        // KPIs with no tracking at all count as delayed.
        $delayed += max($totalKpis - $withData, 0);

        return ['on_track' => $onTrack, 'at_risk' => $atRisk, 'delayed' => $delayed];
    }

    private function recentActivity(array $kpiIds, int $limit): array
    {
        if (empty($kpiIds)) {
            return [];
        }

        return PerformanceTracking::with('kpi.deliverable.commitment.sector')
            ->whereIn('kpi_id', $kpiIds)
            ->orderByDesc('updated_at')->limit($limit)->get()
            ->map(function (PerformanceTracking $t) {
                $sector = optional(optional(optional($t->kpi)->deliverable)->commitment)->sector;

                return [
                    'id' => 'act-'.$t->id,
                    'title' => optional($sector)->sector_name ?? optional($t->kpi)->kpi ?? '—',
                    'subtitle' => 'Q'.$t->quarter.' '.ucwords(strtolower(WireEnums::statusToWire($t->confirmation_status))),
                    'timeLabel' => $t->updated_at ? Carbon::parse($t->updated_at)->diffForHumans(['short' => true]) : '—',
                    'accent' => 'primary',
                    'iconKey' => 'trending_up',
                ];
            })->values()->all();
    }

    private function recentDecisions(User $user, int $limit): array
    {
        return PerformanceTracking::with('kpi.deliverable.commitment.sector')
            ->where('facilitator_confirmed_by', $user->id)
            ->orderByDesc('facilitator_confirmed_at')->limit($limit)->get()
            ->map(function (PerformanceTracking $t) {
                $sector = optional(optional(optional($t->kpi)->deliverable)->commitment)->sector;
                $accepted = $t->facilitator_decision === 'Accept';

                return [
                    'id' => 'dec-'.$t->id,
                    'title' => optional($sector)->sector_name ?? '—',
                    'subtitle' => $accepted ? 'Accepted' : 'Rejected',
                    'timeLabel' => $t->facilitator_confirmed_at ? Carbon::parse($t->facilitator_confirmed_at)->format('M j') : '—',
                    'accent' => $accepted ? 'primary' : 'error',
                ];
            })->values()->all();
    }

    private function logins24h(): int
    {
        if (! Schema::hasTable('oauth_access_tokens')) {
            return 0;
        }

        return (int) DB::table('oauth_access_tokens')->where('created_at', '>=', now()->subDay())->count();
    }

    private function securityRows(int $limit): array
    {
        if (! Schema::hasTable('oauth_access_tokens')) {
            return [];
        }

        return DB::table('oauth_access_tokens as t')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->orderByDesc('t.created_at')->limit($limit)
            ->get(['t.id', 't.created_at', 'u.full_name', 'u.email'])
            ->map(fn ($r) => [
                'id' => 'row-'.$r->id,
                'timestampLabel' => $r->created_at ? Carbon::parse($r->created_at)->format('H:i') : '—',
                'userLabel' => $r->full_name ?? $r->email ?? 'unknown',
                'actionLabel' => 'Sign in',
                'statusLabel' => 'SUCCESS',
                'statusAccent' => 'primary',
            ])->values()->all();
    }

    private function greeting(string $suffix): string
    {
        $hour = (int) now()->format('G');
        $part = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

        return "{$part}, {$suffix}";
    }

    private function currentQuarter(): int
    {
        return (int) ceil((int) date('n') / 3);
    }

    private function planPercent(): float
    {
        return round($this->currentQuarter() / 4 * 100, 1);
    }

    private function pct(float $fraction): float
    {
        return round(min($fraction * 100, 101), 1);
    }
}
