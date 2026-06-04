<?php

namespace App\Services\V2;

use App\Exceptions\V2\ApiException;
use App\Models\Kpi;
use App\Models\KpiTarget;
use App\Models\PerformanceTracking;
use App\Models\User;
use App\Support\V2\Presenters\SectorPresenter;
use App\Support\V2\WireEnums;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The four-role approval workflow (API_REFERENCE.md §11.6) over the §4 state
 * machine. Reads build presentation arrays (computed aggregates, not 1:1 model
 * maps, so they are returned as raw arrays); mutations advance/return the
 * PerformanceTracking lifecycle and cross-check the caller's role + sector access.
 *
 * NOTE: workflow notifications (FCM/in-app) are intentionally NOT dispatched here
 * yet — they belong to the Notifications feature and the web app still sends them
 * for web-initiated actions. State transitions are complete and contract-correct.
 */
class ApprovalService
{
    private const STATE_FOR_ROLE = [
        'sector_head' => 'Pending Sector Head Approval',
        'facilitator' => 'Pending Facilitator',
        'coordinator' => 'Pending Coordinator',
    ];

    public function __construct(private readonly SectorAccessService $access)
    {
    }

    // --- queues (return arrays) ---------------------------------------------

    /**
     * @param  string|null  $sectorId      optional sector id to scope the queue
     * @param  int|null     $year          optional year to scope the queue
     * @param  string|null  $quarterWire   optional q1–q4 to scope the queue
     * @param  string       $sort          'newest' (default) or 'oldest' — by updated_at
     */
    public function coordinatorQueue(
        User $user,
        ?string $sectorId = null,
        ?int $year = null,
        ?string $quarterWire = null,
        string $sort = 'newest',
    ): array {
        return $this->trackingsAwaitingCoordinator($user, $sectorId, $year, $quarterWire, $sort)
            ->map(fn ($t) => $this->queueItem($t))->all();
    }

    public function sectorHeadQueue(User $user, ?string $quarterWire = null): array
    {
        $quarter = WireEnums::wireToQuarter($quarterWire);

        return $this->trackingsAwaitingSectorHead($user, $quarter)
            ->map(fn ($t) => $this->queueItem($t))->all();
    }

    public function sectorHeadBulk(User $user, string $grouping): array
    {
        $tracks = $this->trackingsAwaitingSectorHead($user);

        $groups = $tracks->groupBy(function ($t) use ($grouping) {
            $commitment = optional(optional($t->kpi)->deliverable)->commitment;
            $deliverable = optional($t->kpi)->deliverable;

            return $grouping === 'by_deliverable'
                ? 'Deliverable: '.(optional($deliverable)->deliverable ?? '—')
                : 'Commitment: '.(optional($commitment)->name ?? '—');
        });

        return $groups->map(fn (Collection $items, string $title) => [
            'title' => $title,
            'items' => $items->map(fn ($t) => [
                'id' => (string) $t->id,
                'title' => optional($t->kpi)->kpi ?? '—',
                'value' => (string) ($t->actual_value ?? ''),
                'adminName' => optional($this->approver($t))->full_name ?? 'Data Admin',
            ])->values()->all(),
        ])->values()->all();
    }

    /**
     * @param  string|null  $quarterWire  q1–q4 (null = all quarters)
     * @param  string|null  $sectorId     sector id to narrow the queue to a
     *                                    single assigned sector; silently
     *                                    ignored if the facilitator isn't
     *                                    assigned to it
     */
    public function facilitatorQueue(User $user, string $grouping, ?string $quarterWire = null, ?string $sectorId = null): array
    {
        $quarter = $quarterWire ? WireEnums::wireToQuarter($quarterWire) : null;

        // Narrow within the facilitator's assigned sectors. If the requested
        // sector isn't in that set, fall back to "all assigned" — same effect
        // as the client omitting the param.
        $assigned = $this->access->accessibleSectorIds($user) ?? [];
        $scopedSectorId = null;
        if ($sectorId !== null && $sectorId !== '' && in_array((int) $sectorId, $assigned, true)) {
            $scopedSectorId = (int) $sectorId;
        }

        $tracks = $this->trackingsAwaitingFacilitator($user, $quarter, $scopedSectorId);

        // Groups with no in-scope items naturally don't appear (groupBy only
        // creates buckets for keys that show up in the source collection).
        $groups = $tracks->groupBy(function ($t) use ($grouping) {
            if ($grouping === 'by_kpi') {
                return 'kpi:'.optional($t->kpi)->id;
            }

            return 'sector:'.optional($this->sectorOf($t))->id;
        });

        return $groups->map(function (Collection $items, string $key) use ($grouping) {
            $first = $items->first();
            if ($grouping === 'by_kpi') {
                $title = optional($first->kpi)->kpi ?? '—';
                $id = 'kpi-'.optional($first->kpi)->id;
            } else {
                $sector = $this->sectorOf($first);
                $title = optional($sector)->sector_name ?? '—';
                $id = 'sector-'.optional($sector)->id;
            }

            return [
                'id' => $id,
                'title' => $title,
                'accent' => SectorPresenter::accent($key),
                'items' => $items->map(fn ($t) => $this->queueItem($t))->values()->all(),
            ];
        })->values()->all();
    }

    public function dataAdminMyKpis(User $user, string $filter = 'all', ?string $quarterWire = null, ?int $year = null): array
    {
        $sectorIds = $this->access->accessibleSectorIds($user);
        $year ??= (int) date('Y');

        $kpis = Kpi::with(['performanceTracking', 'deliverable.commitment'])
            ->whereHas('deliverable.commitment', function ($q) use ($sectorIds) {
                if ($sectorIds !== null) {
                    $q->whereIn('sector_id', $sectorIds);
                }
            })
            ->orderBy('kpi')
            ->get();

        return $kpis->map(function (Kpi $kpi) use ($year) {
            $states = $this->quarterStates($kpi->performanceTracking, $year);
            $overall = $this->overallState($states);

            return [
                'id' => 'my-'.$kpi->id,
                'kpiId' => (string) $kpi->id,
                'title' => $kpi->kpi,
                'categoryLabel' => optional(optional($kpi->deliverable)->commitment)->name ?? 'KPI',
                'targetLabel' => $this->targetLabel($kpi, $year),
                'lastUpdateLabel' => $overall === 'rejected'
                    ? 'Action required'
                    : ('Updated '.optional($kpi->performanceTracking->max('updated_at') ? Carbon::parse($kpi->performanceTracking->max('updated_at')) : null)?->diffForHumans(['short' => true]) ?? 'Not yet updated'),
                'quarterStates' => $states,
                'overallState' => $overall,
                'lastUpdateIsError' => $overall === 'rejected',
            ];
        })->filter(fn (array $row) => $this->matchesFilter($row['overallState'], $filter))->values()->all();
    }

    public function submissionDetail(User $user, string $kpiId): array
    {
        $kpi = Kpi::with(['performanceTracking.files', 'deliverable.commitment.sector'])->find($kpiId);
        if (! $kpi) {
            throw ApiException::notFound('Submission not found.');
        }

        $sector = optional(optional($kpi->deliverable)->commitment)->sector;
        if (! $this->access->canAccess($user, optional($sector)->id)) {
            throw ApiException::notFound('Submission not found.');
        }

        $t = $kpi->performanceTracking
            ->filter(fn ($x) => $x->actual_value !== null && $x->actual_value !== '')
            ->sortByDesc(fn ($x) => sprintf('%04d%01d', (int) $x->year, (int) $x->quarter))
            ->first() ?? $kpi->performanceTracking->last();

        if (! $t) {
            throw ApiException::notFound('No submission available for this KPI.');
        }

        return array_filter([
            'id' => (string) $t->id,
            'kpiId' => (string) $kpi->id,
            'kpiTitle' => $kpi->kpi,
            'sectorLabel' => optional($sector)->sector_name ?? '—',
            'quarter' => WireEnums::quarterToWire($t->quarter),
            'state' => WireEnums::statusToWire($t->confirmation_status),
            'trackingDateLabel' => $t->tracking_date ? Carbon::parse($t->tracking_date)->format('j M Y') : '—',
            'milestoneValue' => (string) ($t->milestone ?? '—'),
            'actualValue' => (string) ($t->actual_value ?? '—'),
            'targetValue' => $this->targetValue($kpi, (int) $t->year) ?? '—',
            'remarks' => $t->remarks ?: null,
            'attachments' => $t->files->map(fn ($f) => $f->name ?: 'document')->values()->all(),
        ], fn ($v) => $v !== null);
    }

    // --- mutations -----------------------------------------------------------

    public function review(User $user, string $submissionId, array $params): void
    {
        $t = PerformanceTracking::with('kpi.deliverable.commitment')->find($submissionId);
        if (! $t) {
            throw ApiException::notFound('Submission not found.');
        }

        $sectorId = optional(optional(optional($t->kpi)->deliverable)->commitment)->sector_id;
        if (! $this->access->canAccess($user, $sectorId)) {
            throw ApiException::notFound('Submission not found.');
        }

        $role = $params['role'];
        $this->assertRole($user, $role, (int) $sectorId);

        if (! $this->isAwaitingReviewFromRole($t, $user, $role)) {
            throw ApiException::conflict('This submission is not awaiting your review.');
        }

        $params['decision'] === 'accept'
            ? $this->applyAccept($t, $role, $user, $params)
            : $this->applyReject($t, $role, $user, $params);

        $t->save();
    }

    /**
     * Bulk-accept many submissions in one atomic call. $role is the role the
     * caller is acting in — drives both the per-row permission gate and the
     * lifecycle transition applied:
     *   - sector_head → row moves to "Pending Facilitator"
     *   - coordinator → row moves to "Confirmed" (final approval)
     *
     * All-or-nothing: if any submission isn't approvable for the given role
     * (wrong stage, missing access, missing row), the whole call 409s and no
     * row is touched. Matches the mobile contract — clients refresh the queue
     * on success and don't try to reconcile partial failures.
     */
    public function bulkApprove(User $user, array $submissionIds, string $role): void
    {
        if (! in_array($role, ['sector_head', 'coordinator'], true)) {
            throw ApiException::forbidden("Bulk approval is not supported for the {$role} role.");
        }

        $tracks = PerformanceTracking::with('kpi.deliverable.commitment')
            ->whereIn('id', array_map('intval', $submissionIds))
            ->get();

        if ($tracks->count() !== count(array_unique($submissionIds))) {
            throw ApiException::conflict('One or more submissions could not be found.');
        }

        $wrongStageMessage = $role === 'coordinator'
            ? 'One or more submissions are not awaiting coordinator confirmation.'
            : 'One or more submissions are not awaiting sector-head approval.';

        foreach ($tracks as $t) {
            $sectorId = optional(optional(optional($t->kpi)->deliverable)->commitment)->sector_id;
            if (! $this->access->canAccess($user, $sectorId)) {
                throw ApiException::notFound('Submission not found.');
            }
            $this->assertRole($user, $role, (int) $sectorId);
            if (! $this->isAwaitingReviewFromRole($t, $user, $role)) {
                throw ApiException::conflict($wrongStageMessage);
            }
        }

        DB::transaction(function () use ($tracks, $user, $role) {
            foreach ($tracks as $t) {
                $this->applyAccept($t, $role, $user, []);
                $t->save();
            }
        });
    }

    // --- transition helpers --------------------------------------------------

    /**
     * Whether this submission is awaiting review by the current user acting in
     * the given role — derived from the WHO columns rather than
     * confirmation_status. Lets v2 stay aligned with the web's review semantics
     * (which derive from WHO columns), so a row approved by Sector Head via the
     * web's older flow — which doesn't always update confirmation_status — can
     * still be acted on from the mobile facilitator/coordinator queues.
     */
    private function isAwaitingReviewFromRole(PerformanceTracking $t, User $user, string $role): bool
    {
        $sh = $t->sector_head_approved_by !== null;
        $facDone = $t->facilitator_confirmed_by !== null;
        $coDone = $t->coordinator_confirmed_by !== null;

        switch ($role) {
            case 'sector_head':
                // Sector head hasn't acted yet (no SH approval, no facilitator/
                // coordinator decisions either).
                return ! $sh && ! $facDone && ! $coDone;

            case 'facilitator':
                // SH has approved AND facilitator hasn't yet decided AND
                // coordinator hasn't confirmed. Also let this facilitator pick
                // back up a row they previously rejected (resubmit flow).
                if ($sh && ! $facDone && ! $coDone) {
                    return true;
                }
                if ($facDone
                    && (int) $t->facilitator_confirmed_by === (int) $user->id
                    && $t->facilitator_decision === 'Reject'
                    && ! $coDone) {
                    return true;
                }

                return false;

            case 'coordinator':
                // Facilitator has accepted, coordinator hasn't yet confirmed.
                return $facDone
                    && $t->facilitator_decision === 'Accept'
                    && ! $coDone;
        }

        return false;
    }

    private function applyAccept(PerformanceTracking $t, string $role, User $user, array $params): void
    {
        switch ($role) {
            case 'sector_head':
                $t->sector_head_approved_at = now();
                $t->sector_head_approved_by = $user->id;
                $t->confirmation_status = 'Pending Facilitator';
                break;
            case 'facilitator':
                $t->facilitator_confirmed_at = now();
                $t->facilitator_confirmed_by = $user->id;
                $t->facilitator_decision = 'Accept';
                if (! empty($params['validatedValue'])) {
                    $t->delivery_department_value = $params['validatedValue'];
                }
                if (! empty($params['acceptRemarks'])) {
                    $t->delivery_department_remark = $params['acceptRemarks'];
                }
                $t->confirmation_status = 'Pending Coordinator';
                break;
            case 'coordinator':
                $t->coordinator_confirmed_at = now();
                $t->coordinator_confirmed_by = $user->id;
                $t->coordinator_decision = 'Accept';
                if (! empty($params['validatedValue'])) {
                    $t->delivery_department_value = $params['validatedValue'];
                }
                $t->confirmation_status = 'Confirmed';
                break;
        }
    }

    private function applyReject(PerformanceTracking $t, string $role, User $user, array $params): void
    {
        $reason = $params['rejectionReason'] ?? null;

        switch ($role) {
            case 'sector_head':
                // No dedicated SH-reason column; record it on remarks.
                if ($reason) {
                    $t->remarks = trim('[Sector Head rejection] '.$reason."\n".(string) $t->remarks);
                }
                break;
            case 'facilitator':
                $t->facilitator_confirmed_by = $user->id;
                $t->facilitator_decision = 'Reject';
                $t->facilitator_rejection_reason = $reason;
                break;
            case 'coordinator':
                $t->coordinator_confirmed_by = $user->id;
                $t->coordinator_decision = 'Reject';
                $t->coordinator_rejection_reason = $reason;
                break;
        }

        $t->confirmation_status = 'Rejected';
    }

    private function assertRole(User $user, string $role, int $sectorId): void
    {
        $ok = match ($role) {
            'sector_head' => (function () use ($user, $sectorId) {
                $sh = $user->isSectorHead();

                return $sh && (int) $sh->id === $sectorId;
            })(),
            'facilitator' => $user->isFacilitator() && in_array($sectorId, $user->getAssignedSectorIds() ?: [], true),
            'coordinator' => $user->isCoordinator() || $user->isDeputyCoordinator(),
            default => false,
        };

        if (! $ok) {
            throw ApiException::forbidden("You do not hold the {$role} role for this submission.");
        }
    }

    // --- query + presentation helpers ----------------------------------------

    /** @return Collection<int,PerformanceTracking> */
    private function trackingsInState(User $user, string $state, ?int $quarter = null): Collection
    {
        $sectorIds = $this->access->accessibleSectorIds($user);

        return PerformanceTracking::with(['kpi.deliverable.commitment.sector'])
            ->where('confirmation_status', $state)
            ->when($quarter !== null, fn ($q) => $q->where('quarter', $quarter))
            ->whereHas('kpi.deliverable.commitment', function ($q) use ($sectorIds) {
                if ($sectorIds !== null) {
                    $q->whereIn('sector_id', $sectorIds);
                }
            })
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Facilitator "awaiting review" rows, derived from the WHO columns rather
     * than `confirmation_status` — mirrors the web's
     * UserController::facilitatorAwaitingSectorsWithCounts so the two surfaces
     * agree on the same dataset even when the web's sector-head approval flow
     * leaves `confirmation_status` out of sync.
     *
     * @param  int|null  $quarter        1–4 narrows to that quarter
     * @param  int|null  $singleSectorId narrows to one assigned sector
     * @return Collection<int,PerformanceTracking>
     */
    private function trackingsAwaitingFacilitator(User $user, ?int $quarter = null, ?int $singleSectorId = null): Collection
    {
        $sectorIds = $this->access->accessibleSectorIds($user);
        if ($singleSectorId !== null) {
            $sectorIds = [$singleSectorId];
        }

        return self::applyFacilitatorAwaitingScope(
            PerformanceTracking::with(['kpi.deliverable.commitment.sector']),
            $user,
        )
            ->when($quarter !== null, fn ($q) => $q->where('quarter', $quarter))
            ->whereHas('kpi.deliverable.commitment', function ($q) use ($sectorIds) {
                if ($sectorIds !== null) {
                    $q->whereIn('sector_id', $sectorIds);
                }
            })
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Apply the "awaiting this facilitator's review" WHERE clause to a query
     * builder. Shared between ApprovalService and DashboardService so the
     * mobile dashboard count and the queue endpoint use identical semantics.
     *
     * The clause matches two row populations (matching the web):
     *   - Sector Head has approved, no facilitator decision yet, not yet
     *     escalated to coordinator.
     *   - The same facilitator previously rejected this submission and the
     *     coordinator hasn't acted yet — i.e. it's waiting for the resubmit
     *     they need to re-review.
     */
    public static function applyFacilitatorAwaitingScope(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $outer) use ($user) {
            $outer->where(function (Builder $w) {
                $w->whereNotNull('sector_head_approved_by')
                    ->whereNull('facilitator_confirmed_by')
                    ->whereNull('coordinator_confirmed_by');
            })->orWhere(function (Builder $w) use ($user) {
                $w->where('facilitator_confirmed_by', $user->id)
                    ->where('facilitator_decision', 'Reject')
                    ->whereNull('coordinator_confirmed_by');
            });
        });
    }

    /**
     * Apply the "awaiting coordinator confirmation" WHERE clause to a query
     * builder. Mirrors UserController::pendingForCoordinator on the web:
     * sector head approved, facilitator accepted, coordinator hasn't acted.
     * Facilitator-rejected rows do NOT escalate to the coordinator — they
     * return to data admin for resubmit.
     */
    public static function applyCoordinatorAwaitingScope(Builder $query): Builder
    {
        return $query
            ->whereNotNull('sector_head_approved_by')
            ->whereNotNull('facilitator_confirmed_by')
            ->where('facilitator_decision', 'Accept')
            ->whereNull('coordinator_confirmed_by');
    }

    /**
     * Apply the "awaiting sector head approval" WHERE clause to a query
     * builder. Derives from WHO columns rather than confirmation_status so
     * rows where the data admin submitted but `confirmation_status` is stuck
     * at 'Not Confirmed' (a known production drift) still surface.
     *
     * Rules:
     *  - actual_value is set (data admin has submitted)
     *  - sector_head_approved_by IS NULL (SH hasn't acted yet)
     *  - facilitator_confirmed_by IS NULL (nothing past SH yet)
     *  - coordinator_confirmed_by IS NULL (nothing past SH yet)
     */
    public static function applySectorHeadAwaitingScope(Builder $query): Builder
    {
        return $query
            ->whereNotNull('actual_value')
            ->where('actual_value', '<>', '')
            ->whereNull('sector_head_approved_by')
            ->whereNull('facilitator_confirmed_by')
            ->whereNull('coordinator_confirmed_by');
    }

    /**
     * Coordinator "awaiting" rows, derived from WHO columns so a row whose
     * facilitator-accept flow didn't update confirmation_status still shows
     * up. Mirrors the web's coordinator awaiting query.
     *
     * Optional filters (each applied only when provided):
     *   - $sectorId   narrow to one sector (silently ignored if not in the
     *                 coordinator's accessible set)
     *   - $year       narrow to that reporting year
     *   - $quarter    narrow to that quarter (wire q1–q4)
     * $sort controls ordering: 'newest' (default) or 'oldest' by updated_at.
     *
     * @return Collection<int,PerformanceTracking>
     */
    private function trackingsAwaitingCoordinator(
        User $user,
        ?string $sectorId = null,
        ?int $year = null,
        ?string $quarterWire = null,
        string $sort = 'newest',
    ): Collection {
        $accessibleSectorIds = $this->access->accessibleSectorIds($user);
        $quarter = $quarterWire ? WireEnums::wireToQuarter($quarterWire) : null;

        // Caller-requested sector narrows within the accessible set. If they
        // pass a sector outside their scope, silently ignore the filter
        // (matches the facilitator-queue contract).
        $scopedSectorId = null;
        if ($sectorId !== null && $sectorId !== '') {
            $candidate = (int) $sectorId;
            if ($accessibleSectorIds === null || in_array($candidate, $accessibleSectorIds, true)) {
                $scopedSectorId = $candidate;
            }
        }

        $query = self::applyCoordinatorAwaitingScope(
            PerformanceTracking::with(['kpi.deliverable.commitment.sector']),
        )
            ->when($year !== null, fn ($q) => $q->where('year', $year))
            ->when($quarter !== null, fn ($q) => $q->where('quarter', $quarter))
            ->whereHas('kpi.deliverable.commitment', function ($q) use ($accessibleSectorIds, $scopedSectorId) {
                if ($scopedSectorId !== null) {
                    $q->where('sector_id', $scopedSectorId);
                } elseif ($accessibleSectorIds !== null) {
                    $q->whereIn('sector_id', $accessibleSectorIds);
                }
            });

        return $sort === 'oldest'
            ? $query->orderBy('updated_at')->get()
            : $query->orderByDesc('updated_at')->get();
    }

    /**
     * Sector-head "awaiting" rows, derived from WHO columns rather than the
     * confirmation_status string — production currently has rows submitted by
     * data admin but stuck at 'Not Confirmed' status, which would be invisible
     * to a status-only filter.
     *
     * @return Collection<int,PerformanceTracking>
     */
    private function trackingsAwaitingSectorHead(User $user, ?int $quarter = null): Collection
    {
        $sectorIds = $this->access->accessibleSectorIds($user);

        return self::applySectorHeadAwaitingScope(
            PerformanceTracking::with(['kpi.deliverable.commitment.sector']),
        )
            ->when($quarter !== null, fn ($q) => $q->where('quarter', $quarter))
            ->whereHas('kpi.deliverable.commitment', function ($q) use ($sectorIds) {
                if ($sectorIds !== null) {
                    $q->whereIn('sector_id', $sectorIds);
                }
            })
            ->orderByDesc('updated_at')
            ->get();
    }

    private function queueItem(PerformanceTracking $t): array
    {
        $sector = $this->sectorOf($t);
        $target = $this->targetValue($t->kpi, (int) $t->year);

        $stats = [];
        if ($target !== null) {
            $stats[] = ['iconKey' => 'flag', 'label' => 'Target '.$target];
        }
        if ($t->actual_value !== null && $t->actual_value !== '') {
            $stats[] = ['iconKey' => 'check_circle', 'label' => 'Actual '.$t->actual_value, 'accent' => 'primary'];
        }

        return array_filter([
            'id' => (string) $t->id,
            'kpiId' => (string) $t->kpi_id,
            'kpiTitle' => optional($t->kpi)->kpi ?? '—',
            'sectorLabel' => optional($sector)->sector_name ?? '—',
            'sectorAccent' => SectorPresenter::accent(optional($sector)->id ?? 0),
            'state' => WireEnums::statusToWire($t->confirmation_status),
            'mda' => optional($sector)->ministry ?: null,
            'updatedAgo' => $t->updated_at ? 'Updated '.Carbon::parse($t->updated_at)->diffForHumans(['short' => true]) : null,
            'quarter' => WireEnums::quarterToWire($t->quarter),
            'metricLabel' => 'Actual',
            'metricValue' => $t->actual_value !== null ? (string) $t->actual_value : null,
            'actualValue' => $t->actual_value !== null ? (string) $t->actual_value : null,
            'stats' => $stats,
        ], fn ($v) => $v !== null && $v !== []);
    }

    private function quarterStates(Collection $tracks, int $year): array
    {
        return collect(range(1, 4))->map(function (int $q) use ($tracks, $year) {
            $t = $tracks->first(fn ($x) => (int) $x->quarter === $q && (int) $x->year === $year);
            if (! $t || $t->actual_value === null || $t->actual_value === '') {
                return 'pending_entry';
            }

            return WireEnums::statusToWire($t->confirmation_status);
        })->all();
    }

    private function overallState(array $quarterStates): string
    {
        foreach (['rejected', 'pending_sector_head', 'pending_facilitator', 'pending_coordinator', 'pending_entry'] as $priority) {
            if (in_array($priority, $quarterStates, true)) {
                return $priority;
            }
        }

        return 'confirmed';
    }

    private function matchesFilter(string $overall, string $filter): bool
    {
        return match ($filter) {
            'pending_entry' => $overall === 'pending_entry',
            'pending_sh' => $overall === 'pending_sector_head',
            'confirmed' => $overall === 'confirmed',
            default => true, // all
        };
    }

    private function targetValue(?Kpi $kpi, int $year): ?string
    {
        if (! $kpi) {
            return null;
        }
        $target = optional(KpiTarget::where('kpi_id', $kpi->id)->where('year', $year)->first())->target;
        if ($target === null) {
            return null;
        }
        $unit = trim((string) $kpi->unit_of_measurement);
        $value = rtrim(rtrim((string) $target, '0'), '.');

        return $unit === '%' ? $value.'%' : trim($value.' '.$unit);
    }

    private function targetLabel(Kpi $kpi, int $year): string
    {
        $value = $this->targetValue($kpi, $year);

        return $value ? "Target: {$value}" : 'Target: —';
    }

    private function sectorOf(PerformanceTracking $t)
    {
        return optional(optional($t->kpi)->deliverable)->commitment?->sector;
    }

    private function approver(PerformanceTracking $t): ?User
    {
        return $t->sector_head_approved_by ? User::find($t->sector_head_approved_by) : null;
    }
}
