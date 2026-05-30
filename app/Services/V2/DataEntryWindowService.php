<?php

namespace App\Services\V2;

use App\Exceptions\V2\ApiException;
use App\Models\DataEntryAccess;
use App\Models\Framework;
use App\Models\PerformanceTracking;
use App\Models\Sector;
use App\Models\User;
use App\Support\V2\Presenters\SectorPresenter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Data-entry window management (API_REFERENCE.md §11.7). All endpoints are
 * coordinator-only (Coordinator or Deputy Coordinator) — else 403.
 *
 * Operates on `data_entry_accesses`: status open/closed/override, with deadlines
 * and override metadata. The list ensures a row exists for every sector in the
 * active framework for the requested (year, quarter), seeding closed/locked rows
 * if missing (mirrors the web's lazy initialization).
 */
class DataEntryWindowService
{
    public function listWindows(User $user, ?int $year, ?string $quarterWire): array
    {
        $this->assert($user);

        [$year, $quarter] = $this->resolvePeriod($year, $quarterWire);
        $this->ensureRows($year, $quarter);

        $sectorIds = $this->sectorIdsForYear($year);

        // Constraint:
        //   - Restrict to the year's framework sectors. Web-side flows
        //     (DataEntryAccessController::lockAll / unlockAll / initializeQuarter)
        //     seed rows for EVERY sector regardless of framework, leaving cross-
        //     framework rows we must hide here.
        //   - Dedup by sector_id (keep newest row) defensively — production tables
        //     created from older SQL dumps may not carry the unique constraint
        //     the recent migration adds.
        return DataEntryAccess::with('sector')
            ->where('year', $year)->where('quarter', $quarter)
            ->whereIn('sector_id', $sectorIds)
            ->whereHas('sector')
            ->orderByDesc('id')
            ->get()
            ->unique('sector_id')
            ->sortBy(fn ($r) => optional($r->sector)->sector_name)
            ->map(fn (DataEntryAccess $r) => $this->row($r, $year, $quarter))
            ->values()->all();
    }

    public function stats(User $user, ?int $year, ?string $quarterWire): array
    {
        $this->assert($user);

        [$year, $quarter] = $this->resolvePeriod($year, $quarterWire);
        $this->ensureRows($year, $quarter);

        $sectorIds = $this->sectorIdsForYear($year);
        $total = $sectorIds->count();

        // distinct sector_id (not raw row count) — protects against duplicate
        // rows that may exist on production tables imported from older dumps
        // without the unique(sector_id, year, quarter) constraint.
        $open = DataEntryAccess::where('year', $year)->where('quarter', $quarter)
            ->whereIn('sector_id', $sectorIds)
            ->whereIn('status', ['open', 'override'])
            ->distinct('sector_id')->count('sector_id');
        $rate = $this->submissionRate($year, $quarter, $sectorIds);

        return [
            'totalSectors' => (int) $total,
            'openSectors' => (int) $open,
            'submissionRateLabel' => $rate.'%',
        ];
    }

    public function lockAll(User $user): void
    {
        $this->assert($user);

        [$year, $quarter] = $this->resolvePeriod(null, null);
        $this->ensureRows($year, $quarter);
        DataEntryAccess::where('year', $year)->where('quarter', $quarter)
            ->update(['status' => 'closed']);
    }

    public function unlockAll(User $user): void
    {
        $this->assert($user);

        [$year, $quarter] = $this->resolvePeriod(null, null);
        $this->ensureRows($year, $quarter);
        DataEntryAccess::where('year', $year)->where('quarter', $quarter)
            ->update(['status' => 'open']);
    }

    public function open(User $user, string $sectorId): void
    {
        $row = $this->findOrCreateRow($user, $sectorId);
        $row->status = 'open';
        $row->save();
    }

    public function lock(User $user, string $sectorId): void
    {
        $row = $this->findOrCreateRow($user, $sectorId);
        $row->status = 'closed';
        $row->save();
    }

    public function grantOverride(User $user, string $sectorId, array $params): void
    {
        $row = $this->findOrCreateRow($user, $sectorId);
        $row->status = 'override';
        $row->override_reason = $params['reason'];
        if (! empty($params['expiresAt'])) {
            $row->override_deadline = Carbon::parse($params['expiresAt']);
        }
        $row->granted_by = $user->id;
        $row->granted_at = now();
        $row->save();
    }

    // --- helpers -------------------------------------------------------------

    private function assert(User $user): void
    {
        if (! ($user->isCoordinator() || $user->isDeputyCoordinator())) {
            throw ApiException::forbidden('Data-entry windows are only manageable by Coordinators.');
        }
    }

    private function resolvePeriod(?int $year, ?string $quarterWire): array
    {
        $quarter = $quarterWire ? \App\Support\V2\WireEnums::wireToQuarter($quarterWire) : null;

        return [
            (int) ($year ?? optional(Framework::where('status', 'Active')->first())->year ?? date('Y')),
            (int) ($quarter ?? ceil((int) date('n') / 3)),
        ];
    }

    /**
     * Sectors belonging to the framework that matches the requested year. Falls
     * back to the Active framework's sectors when the year doesn't have its own
     * framework yet, then to an empty collection. Always returns unique ids.
     */
    private function sectorIdsForYear(int $year)
    {
        $fw = Framework::where('year', $year)->first()
            ?? Framework::where('status', 'Active')->first();

        return $fw
            ? Sector::where('framework_id', $fw->id)->pluck('id')->unique()->values()
            : collect();
    }

    private function ensureRows(int $year, int $quarter): void
    {
        $sectorIds = $this->sectorIdsForYear($year);
        if ($sectorIds->isEmpty()) {
            return;
        }

        $existing = DataEntryAccess::where('year', $year)->where('quarter', $quarter)
            ->whereIn('sector_id', $sectorIds)->pluck('sector_id')->unique();

        $missing = $sectorIds->diff($existing);
        if ($missing->isEmpty()) {
            return;
        }

        $deadline = Carbon::create($year, $quarter * 3, 1)->endOfMonth()->addWeeks(2)->toDateString();
        DataEntryAccess::insert($missing->map(fn ($id) => [
            'sector_id' => $id, 'year' => $year, 'quarter' => $quarter,
            'deadline_date' => $deadline, 'status' => 'closed',
            'created_at' => now(), 'updated_at' => now(),
        ])->all());
    }

    private function findOrCreateRow(User $user, string $sectorId): DataEntryAccess
    {
        $this->assert($user);

        if (! Sector::whereKey($sectorId)->exists()) {
            throw ApiException::notFound('Sector not found.');
        }

        [$year, $quarter] = $this->resolvePeriod(null, null);
        $deadline = Carbon::create($year, $quarter * 3, 1)->endOfMonth()->addWeeks(2)->toDateString();

        return DataEntryAccess::firstOrCreate(
            ['sector_id' => $sectorId, 'year' => $year, 'quarter' => $quarter],
            ['deadline_date' => $deadline, 'status' => 'closed'],
        );
    }

    private function row(DataEntryAccess $r, int $year, int $quarter): array
    {
        $sector = $r->sector;
        $statusWire = $r->status === 'closed' ? 'locked' : 'open'; // override → open
        $deadline = $r->override_deadline ?: $r->deadline_date;

        return [
            'sectorId' => (string) $r->sector_id,
            'sectorName' => optional($sector)->sector_name ?? '—',
            'accent' => SectorPresenter::accent($r->sector_id),
            'status' => $statusWire,
            'lastUpdatedLabel' => $r->updated_at ? 'Updated '.Carbon::parse($r->updated_at)->diffForHumans(['short' => true]) : '—',
            'quarterLabel' => 'Q'.$quarter.' '.$year,
            'deadlineLabel' => $deadline ? 'Due '.Carbon::parse($deadline)->format('j M') : '—',
        ];
    }

    private function submissionRate(int $year, int $quarter, $sectorIds = null): int
    {
        $sectorIds ??= $this->sectorIdsForYear($year);
        $total = $sectorIds->count();
        if ($total === 0) {
            return 0;
        }

        $submitted = DB::table('performance_trackings as pt')
            ->join('kpis as k', 'k.id', '=', 'pt.kpi_id')
            ->join('deliverables as d', 'd.id', '=', 'k.deliverable_id')
            ->join('commitments as c', 'c.id', '=', 'd.commitment_id')
            ->where('pt.year', $year)->where('pt.quarter', $quarter)
            ->whereIn('c.sector_id', $sectorIds)
            ->whereNotNull('pt.actual_value')->where('pt.actual_value', '!=', '')
            ->distinct('c.sector_id')->count('c.sector_id');

        return (int) round($submitted / $total * 100);
    }
}
