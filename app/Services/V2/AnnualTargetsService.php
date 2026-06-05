<?php

namespace App\Services\V2;

use App\Exceptions\V2\ApiException;
use App\Models\Deliverable;
use App\Models\Kpi;
use App\Models\KpiTarget;
use App\Models\PerformanceTracking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Annual KPI targets per deliverable + year (API_REFERENCE.md §11.4.6).
 *
 * Authoritative source of truth is the `kpi_targets` table; rows are
 * scoped by (kpi_id, year). PDCU users (Coordinator, Deputy Coordinator,
 * Facilitator) may set targets for KPIs within sectors they can access.
 *
 * Save semantics: partial-update. Targets not included in the payload are
 * left untouched. To clear a target, a future DELETE endpoint will be added.
 */
class AnnualTargetsService
{
    public function __construct(private readonly SectorAccessService $access)
    {
    }

    /**
     * List the deliverable's KPIs with their target for the requested year.
     * Each row is shaped exactly as the mobile contract specifies; targetValue
     * is omitted (key absent) when no target row exists for that KPI + year.
     *
     * @return array<int, array<string, mixed>>
     */
    public function index(User $user, string $deliverableId, int $year): array
    {
        $deliverable = $this->loadDeliverable($deliverableId);
        $this->authorize($user, $deliverable);

        $kpis = Kpi::with('deliverable.commitment')
            ->where('deliverable_id', $deliverable->id)
            ->orderBy('id')
            ->get();

        $kpiIds = $kpis->pluck('id')->all();

        $existingTargets = KpiTarget::where('year', $year)
            ->whereIn('kpi_id', $kpiIds)
            ->get()
            ->keyBy('kpi_id');

        // Baseline = latest confirmed actual_value per KPI ("where we stand
        // right now"), so the target-setting UX has a meaningful reference.
        // Falls back to "" when nothing has been confirmed yet.
        $baselines = $this->latestConfirmedActuals($kpiIds);

        $category = optional($deliverable->commitment)->name ?? '—';

        return $kpis->map(function (Kpi $kpi) use ($existingTargets, $baselines, $category) {
            $target = $existingTargets->get($kpi->id);
            $hasTarget = $target && $target->target !== null && (string) $target->target !== '';

            return array_filter([
                'kpiId' => (string) $kpi->id,
                'category' => $category,
                'title' => $kpi->kpi,
                'baselineValue' => $baselines[$kpi->id] ?? '0',
                'baselineUnit' => $kpi->unit_of_measurement ?: '',
                'targetUnit' => $kpi->unit_of_measurement ?: '',
                'targetValue' => $hasTarget ? $this->formatNumber($target->target) : null,
            ], fn ($v) => $v !== null);
        })->all();
    }

    /**
     * Latest confirmed actual_value per kpi_id, as a display string. KPIs
     * with no confirmed submission yet are absent from the result.
     *
     * @param  int[]  $kpiIds
     * @return array<int, string>
     */
    private function latestConfirmedActuals(array $kpiIds): array
    {
        if (empty($kpiIds)) {
            return [];
        }

        return PerformanceTracking::whereIn('kpi_id', $kpiIds)
            ->where('confirmation_status', 'Confirmed')
            ->whereNotNull('actual_value')
            ->where('actual_value', '<>', '')
            ->orderByDesc('year')
            ->orderByDesc('quarter')
            ->get(['kpi_id', 'actual_value'])
            ->groupBy('kpi_id')
            ->map(fn ($rows) => $this->formatNumber($rows->first()->actual_value))
            ->all();
    }

    /**
     * Upsert each `{kpiId, value}` into kpi_targets for the named year. Atomic
     * — any KPI not belonging to this deliverable, or any non-numeric value,
     * aborts the entire batch (422). Targets not present in the payload are
     * left untouched.
     *
     * @param  array<int, array{kpiId: string, value: string}>  $targets
     */
    public function save(User $user, string $deliverableId, int $year, array $targets): void
    {
        $deliverable = $this->loadDeliverable($deliverableId);
        $this->authorize($user, $deliverable);

        $validKpiIds = Kpi::where('deliverable_id', $deliverable->id)->pluck('id')->all();
        $validKpiIdSet = array_flip(array_map('intval', $validKpiIds));

        // Preflight: every kpiId must belong to this deliverable and every
        // value must be a non-negative number. Reject the whole batch on any
        // failure rather than partially commit (matches the mobile contract:
        // "treat omitted as no change", which implies "what we DID send must
        // all land or nothing should").
        $fieldErrors = [];
        foreach ($targets as $i => $entry) {
            $kpiId = isset($entry['kpiId']) ? (int) $entry['kpiId'] : 0;
            if (! isset($validKpiIdSet[$kpiId])) {
                $fieldErrors["targets.$i.kpiId"] = ['kpi does not belong to this deliverable'];

                continue;
            }
            $value = $entry['value'] ?? null;
            if ($value === null || $value === '' || ! is_numeric($value) || (float) $value < 0) {
                $fieldErrors["targets.$i.value"] = ['must be a non-negative numeric string'];
            }
        }
        if (! empty($fieldErrors)) {
            throw ApiException::unprocessable('Some annual targets are invalid.', $fieldErrors);
        }

        DB::transaction(function () use ($targets, $year) {
            foreach ($targets as $entry) {
                // KpiTarget has no $fillable; construct/find by hand to avoid
                // mass-assignment.
                $row = KpiTarget::where('kpi_id', (int) $entry['kpiId'])
                    ->where('year', $year)
                    ->first();
                if (! $row) {
                    $row = new KpiTarget();
                    $row->kpi_id = (int) $entry['kpiId'];
                    $row->year = $year;
                }
                $row->target = (string) $entry['value'];
                $row->save();
            }
        });
    }

    // --- helpers -------------------------------------------------------------

    private function loadDeliverable(string $deliverableId): Deliverable
    {
        $deliverable = Deliverable::with('commitment')->find($deliverableId);
        if (! $deliverable) {
            throw ApiException::notFound('Deliverable not found.');
        }

        return $deliverable;
    }

    private function authorize(User $user, Deliverable $deliverable): void
    {
        // Mirror the web's Set Target gate: PDCU operational roles only.
        if (! $user->isDeliveryUnit()) {
            throw ApiException::forbidden('Only PDCU users may set annual KPI targets.');
        }

        // Plus sector-access: facilitators only act on their assigned sectors.
        $sectorId = optional($deliverable->commitment)->sector_id;
        if (! $this->access->canAccess($user, $sectorId)) {
            throw ApiException::notFound('Deliverable not found.');
        }
    }

    /**
     * Return the stored value verbatim as a string — no float-cast, no
     * trailing-zero strip. The mobile contract treats value fields as opaque
     * strings (API_REFERENCE §11.4.4–§11.4.7), so we never reformat. The only
     * normalisation is empty → ''.
     */
    private function formatNumber(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return (string) $value;
    }
}
