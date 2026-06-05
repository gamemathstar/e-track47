<?php

namespace App\Services\V2;

use App\Exceptions\V2\ApiException;
use App\Models\File;
use App\Models\Framework;
use App\Models\Kpi;
use App\Models\KpiTarget;
use App\Models\PerformanceTracking;
use App\Models\User;
use App\Support\V2\WireEnums;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * KPI tracking reads + mutations (API_REFERENCE.md §11.4). Reuses the existing
 * Kpi / KpiTarget / PerformanceTracking models and the §4 approval state machine.
 * Pure derived presentation is attached to the Kpi model as transient attributes
 * for KpiResource (GR1: models are never persisted with these).
 */
class KpiTrackingService
{
    private const STATUS_LABELS = [
        'Confirmed' => 'CONFIRMED',
        'Rejected' => 'REJECTED',
        'Pending Sector Head Approval' => 'PENDING SECTOR HEAD',
        'Pending Facilitator' => 'PENDING FACILITATOR',
        'Pending Coordinator' => 'PENDING COORDINATOR',
        'Not Confirmed' => 'NOT CONFIRMED',
    ];

    public function __construct(private readonly SectorAccessService $access)
    {
    }

    // --- reads ---------------------------------------------------------------

    /** @return Collection<int,Kpi> */
    public function listForDeliverable(User $user, string $deliverableId): Collection
    {
        $kpis = Kpi::with(['performanceTracking', 'deliverable.commitment'])
            ->where('deliverable_id', $deliverableId)
            ->orderBy('kpi')
            ->get();

        if ($kpis->isEmpty()) {
            // Authorize via the deliverable's sector even when there are no KPIs.
            $this->authorizeRead($user, $this->sectorIdForDeliverable($deliverableId));

            return $kpis;
        }

        $this->authorizeRead($user, $this->sectorIdForKpi($kpis->first()));
        $kpis->each(fn (Kpi $kpi) => $this->attachSummary($kpi));

        return $kpis;
    }

    public function getKpi(User $user, string $id): Kpi
    {
        $kpi = Kpi::with(['performanceTracking.files', 'deliverable.commitment.sector'])->find($id);

        if (! $kpi) {
            throw ApiException::notFound('KPI not found.');
        }

        $this->authorizeRead($user, $this->sectorIdForKpi($kpi));
        $this->attachSummary($kpi);
        $this->attachDetail($kpi);

        return $kpi;
    }

    // --- mutations (queued; controller returns 202) --------------------------

    public function submitPerformance(User $user, string $kpiId, array $params): void
    {
        $kpi = $this->findKpiOrFail($kpiId);
        $this->authorizeDataEntry($user, $this->sectorIdForKpi($kpi));

        $tracking = $this->upsertTracking($kpi, WireEnums::wireToQuarter($params['quarter']), $this->resolvedYear($kpi));
        $this->assertNotLocked($tracking);

        $tracking->actual_value = $params['actualValue'];
        if (array_key_exists('remarks', $params) && $params['remarks'] !== null) {
            $tracking->remarks = $params['remarks'];
        }

        // (Re)submit to the sector head — clear any downstream review state so a
        // resubmission after rejection restarts the workflow cleanly.
        $tracking->confirmation_status = 'Pending Sector Head Approval';
        $tracking->sector_head_approved_at = null;
        $tracking->sector_head_approved_by = null;
        $tracking->facilitator_confirmed_at = null;
        $tracking->facilitator_confirmed_by = null;
        $tracking->facilitator_decision = null;
        $tracking->facilitator_rejection_reason = null;
        $tracking->coordinator_confirmed_at = null;
        $tracking->coordinator_confirmed_by = null;
        $tracking->coordinator_decision = null;
        $tracking->coordinator_rejection_reason = null;
        $tracking->save();

        $this->attachEvidence($tracking, $params['evidenceDocumentIds'] ?? []);
    }

    /**
     * Read the milestone already saved for this KPI + quarter + year. Returns
     * `['value' => null]` when no row exists or the row has no milestone yet
     * — the mobile contract treats null as "no milestone" so the field renders
     * blank rather than 404'ing the sheet.
     *
     * Authorization is the same as the KPI read endpoints: any user who can
     * see the KPI can see its milestones (not gated to PDCU like the writes).
     *
     * @return array{value: string|null}
     */
    public function getMilestone(User $user, string $kpiId, string $quarterWire, int $year): array
    {
        $kpi = $this->findKpiOrFail($kpiId);
        $this->authorizeRead($user, $this->sectorIdForKpi($kpi));

        $quarter = WireEnums::wireToQuarter($quarterWire);
        $tracking = PerformanceTracking::where('kpi_id', $kpi->id)
            ->where('quarter', $quarter)
            ->where('year', $year)
            ->first();

        $value = $tracking && $tracking->milestone !== null && (string) $tracking->milestone !== ''
            ? (string) $tracking->milestone
            : null;

        return ['value' => $value];
    }

    public function setMilestone(User $user, string $kpiId, array $params): void
    {
        $kpi = $this->findKpiOrFail($kpiId);
        $this->authorizePdcu($user, $this->sectorIdForKpi($kpi));

        $tracking = $this->upsertTracking($kpi, WireEnums::wireToQuarter($params['quarter']), (int) $params['year']);
        $this->assertNotLocked($tracking);

        $tracking->milestone = $params['value'];
        if (! empty($params['trackingDate'])) {
            $tracking->tracking_date = Carbon::parse($params['trackingDate']);
        }
        if (array_key_exists('remarks', $params) && $params['remarks'] !== null) {
            $tracking->remarks = $params['remarks'];
        }
        $tracking->confirmation_status ??= 'Not Confirmed';
        $tracking->save();
    }

    public function addTrackingEntry(User $user, string $kpiId, array $params): void
    {
        $kpi = $this->findKpiOrFail($kpiId);
        $this->authorizeDataEntry($user, $this->sectorIdForKpi($kpi));

        $tracking = $this->upsertTracking($kpi, WireEnums::wireToQuarter($params['quarter']), (int) $params['year']);
        $this->assertNotLocked($tracking);

        $tracking->tracking_date = Carbon::parse($params['trackingDate']);
        $tracking->actual_value = $params['actualValue'];
        if (array_key_exists('remarks', $params) && $params['remarks'] !== null) {
            $tracking->remarks = $params['remarks'];
        }
        $tracking->confirmation_status ??= 'Not Confirmed';
        $tracking->save();

        $this->attachEvidence($tracking, $params['evidenceDocumentIds'] ?? []);
    }

    // --- attach derived presentation -----------------------------------------

    private function attachSummary(Kpi $kpi): void
    {
        $year = $this->resolvedYear($kpi);
        $tracks = $kpi->performanceTracking;
        $fraction = $this->averageFraction($tracks);

        $kpi->setAttribute('v_deliverable_id', (string) $kpi->deliverable_id);
        $kpi->setAttribute('v_title', $kpi->kpi);
        $kpi->setAttribute('v_target_label', $this->targetLabel($kpi, $year));
        $kpi->setAttribute('v_status', $this->status($tracks, $fraction));
        $kpi->setAttribute('v_status_label', ucfirst($this->status($tracks, $fraction)));
        $kpi->setAttribute('v_quarters_overview', $this->quartersOverview($tracks, $year));
        $kpi->setAttribute('v_last_updated_label', $this->lastUpdatedLabel($tracks));
    }

    private function attachDetail(Kpi $kpi): void
    {
        $year = $this->resolvedYear($kpi);
        $tracks = $kpi->performanceTracking;
        $fraction = $this->averageFraction($tracks);
        $latest = $this->latestTracking($tracks);
        $commitment = optional($kpi->deliverable)->commitment;
        $sectorName = optional(optional($commitment)->sector)->sector_name;

        $kpi->setAttribute('v_unit', $kpi->unit_of_measurement ?: null);
        $kpi->setAttribute('v_target_value', $this->targetValue($kpi, $year));
        $kpi->setAttribute('v_year', $year);
        $kpi->setAttribute('v_parent_commitment_title', optional($commitment)->name);
        $kpi->setAttribute('v_breadcrumb', $commitment && $sectorName ? "{$commitment->name} › {$sectorName}" : null);
        $kpi->setAttribute('v_progress_percent', round($fraction, 4));
        $kpi->setAttribute('v_submissions', $this->submissions($tracks));
        $kpi->setAttribute('v_supporting_documents', $this->supportingDocuments($tracks));

        if ($latest) {
            $kpi->setAttribute('v_hero_eyebrow', 'CURRENT PERFORMANCE');
            $kpi->setAttribute('v_hero_value', (string) ($latest->actual_value ?? '—'));
            $kpi->setAttribute('v_hero_suffix', 'submitted');
            $kpi->setAttribute('v_hero_subtext', 'Q'.$latest->quarter.' '.ucwords(strtolower(self::STATUS_LABELS[$latest->confirmation_status] ?? '')));
            $kpi->setAttribute('v_active_quarter', WireEnums::quarterToWire($latest->quarter));
            $kpi->setAttribute('v_active_milestone_value', $latest->milestone !== null ? (string) $latest->milestone : null);
            $kpi->setAttribute('v_active_tracking_date_label', $latest->tracking_date ? Carbon::parse($latest->tracking_date)->format('j M Y') : null);
        }
    }

    // --- derivation helpers --------------------------------------------------

    private function quartersOverview(Collection $tracks, int $year): array
    {
        return collect(range(1, 4))->map(function (int $q) use ($tracks, $year) {
            $t = $tracks->first(fn ($x) => (int) $x->quarter === $q && (int) $x->year === $year);
            if (! $t || $t->actual_value === null || $t->actual_value === '') {
                return 'pending';
            }

            return $t->confirmation_status === 'Confirmed' ? 'completed' : 'in_progress';
        })->all();
    }

    private function submissions(Collection $tracks): array
    {
        return $tracks
            ->filter(fn ($t) => $t->actual_value !== null && $t->actual_value !== '')
            ->sortBy('quarter')
            ->map(function ($t) {
                $confirmed = $t->confirmation_status === 'Confirmed';

                return array_filter([
                    'quarter' => WireEnums::quarterToWire($t->quarter),
                    'status' => $confirmed ? 'confirmed' : 'pending',
                    'title' => 'Q'.$t->quarter.' Submission',
                    'milestone' => (string) ($t->milestone ?? ''),
                    'actual' => (string) ($t->actual_value ?? ''),
                    'date' => $t->tracking_date ? Carbon::parse($t->tracking_date)->format('M j, Y') : null,
                    'remarks' => $t->remarks ?: null,
                    'statusLabel' => self::STATUS_LABELS[$t->confirmation_status] ?? null,
                    'reviewCtaLabel' => $confirmed ? null : 'Review Submission',
                ], fn ($v) => $v !== null);
            })->values()->all();
    }

    private function supportingDocuments(Collection $tracks): array
    {
        return $tracks->flatMap(fn ($t) => $t->files)->map(function (File $f) {
            return array_filter([
                'id' => (string) $f->id,
                'filename' => $f->name ?: 'document',
                'kind' => $this->fileKind($f),
                'sizeLabel' => $f->size ? $this->humanSize((int) $f->size) : null,
            ], fn ($v) => $v !== null);
        })->values()->all();
    }

    private function status(Collection $tracks, float $fraction): string
    {
        $hasData = $tracks->contains(fn ($t) => $t->actual_value !== null && $t->actual_value !== '');
        if (! $hasData) {
            return 'pending';
        }

        return match (true) {
            $fraction >= 0.85 => 'active',
            $fraction >= 0.6 => 'stable',
            default => 'lagging',
        };
    }

    private function averageFraction(Collection $tracks): float
    {
        $fractions = $tracks->map(function ($t) {
            $value = $t->delivery_department_value !== null && $t->delivery_department_value !== ''
                ? $t->delivery_department_value
                : $t->actual_value;
            $milestone = $t->milestone;
            if (! is_numeric($value) || ! is_numeric($milestone) || (float) $milestone == 0.0) {
                return null;
            }

            return min((float) $value / (float) $milestone, 1.0);
        })->filter(fn ($v) => $v !== null);

        return $fractions->isEmpty() ? 0.0 : (float) $fractions->avg();
    }

    private function targetLabel(Kpi $kpi, int $year): string
    {
        $value = $this->targetValue($kpi, $year);

        return $value ? "Target: {$value}" : 'Target: —';
    }

    private function targetValue(Kpi $kpi, int $year): ?string
    {
        $target = optional(KpiTarget::where('kpi_id', $kpi->id)->where('year', $year)->first())->target;
        if ($target === null) {
            return null;
        }

        $unit = trim((string) $kpi->unit_of_measurement);

        return $unit === '%' ? rtrim(rtrim((string) $target, '0'), '.').'%' : trim(rtrim(rtrim((string) $target, '0'), '.').' '.$unit);
    }

    private function lastUpdatedLabel(Collection $tracks): string
    {
        $latest = $tracks->max('updated_at');

        return $latest ? 'Updated '.Carbon::parse($latest)->format('M j') : 'Not yet updated';
    }

    private function latestTracking(Collection $tracks): ?PerformanceTracking
    {
        return $tracks->sortByDesc(fn ($t) => sprintf('%04d%01d', (int) $t->year, (int) $t->quarter))->first();
    }

    private function fileKind(File $file): string
    {
        $hint = strtolower((string) ($file->type ?: $file->name));

        return str_contains($hint, 'pdf') ? 'pdf'
            : (preg_match('/(jpg|jpeg|png|gif|webp|image)/', $hint) ? 'image' : 'pdf');
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024).' KB';
        }

        return $bytes.' B';
    }

    // --- mutation helpers ----------------------------------------------------

    private function upsertTracking(Kpi $kpi, ?int $quarter, int $year): PerformanceTracking
    {
        $tracking = PerformanceTracking::firstOrNew([
            'kpi_id' => $kpi->id,
            'quarter' => $quarter,
            'year' => $year,
        ]);
        $tracking->framework_id = $tracking->framework_id ?: $kpi->framework_id;

        return $tracking;
    }

    private function assertNotLocked(PerformanceTracking $tracking): void
    {
        if ($tracking->exists && $tracking->confirmation_status === 'Confirmed') {
            throw ApiException::conflict('This quarter is already confirmed and locked.');
        }
    }

    private function attachEvidence(PerformanceTracking $tracking, array $evidenceIds): void
    {
        $ids = array_filter(array_map('intval', $evidenceIds));
        if (empty($ids)) {
            return;
        }

        File::whereIn('id', $ids)->update([
            'fileable_id' => $tracking->id,
            'fileable_type' => PerformanceTracking::class,
        ]);
    }

    // --- resolution & authorization ------------------------------------------

    private function findKpiOrFail(string $id): Kpi
    {
        $kpi = Kpi::with('deliverable.commitment')->find($id);
        if (! $kpi) {
            throw ApiException::notFound('KPI not found.');
        }

        return $kpi;
    }

    private function resolvedYear(Kpi $kpi): int
    {
        return (int) ($kpi->year ?: optional(Framework::where('status', 'Active')->first())->year ?: date('Y'));
    }

    private function sectorIdForKpi(Kpi $kpi): ?int
    {
        return optional(optional($kpi->deliverable)->commitment)->sector_id;
    }

    private function sectorIdForDeliverable(string $deliverableId): ?int
    {
        return optional(\App\Models\Deliverable::with('commitment')->find($deliverableId))?->commitment?->sector_id;
    }

    private function authorizeRead(User $user, ?int $sectorId): void
    {
        if (! $this->access->canAccess($user, $sectorId)) {
            throw ApiException::notFound('KPI not found.');
        }
    }

    private function authorizeDataEntry(User $user, ?int $sectorId): void
    {
        $this->authorizeRead($user, $sectorId);

        if ($user->canAccessAllSectors()) {
            return;
        }
        $own = $user->isDataAdmin();
        if ($own && (int) $own->id === (int) $sectorId) {
            return;
        }

        throw ApiException::forbidden('Only the sector Data Admin may enter performance data.');
    }

    private function authorizePdcu(User $user, ?int $sectorId): void
    {
        $this->authorizeRead($user, $sectorId);

        if ($user->canAccessAllSectors() || $user->isDeliveryUnit()) {
            return;
        }

        throw ApiException::forbidden('Only PDCU roles may set milestones.');
    }
}
