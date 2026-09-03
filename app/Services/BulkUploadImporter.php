<?php

namespace App\Services;

use App\Models\Commitment;
use App\Models\Deliverable;
use App\Models\Framework;
use App\Models\Kpi;
use App\Models\KpiTarget;
use App\Models\Notification;
use App\Models\PerformanceTracking;
use App\Models\Sector;
use Illuminate\Support\Facades\DB;
use Throwable;

class BulkUploadImporter
{
    /** @var array<string, Commitment> */
    private array $commitmentCache = [];

    /** @var array<string, Deliverable> */
    private array $deliverableCache = [];

    /** @var array<string, Kpi> */
    private array $kpiCache = [];

    /** @var \Illuminate\Support\Collection<int, Commitment>|null */
    private $sectorCommitments = null;

    /** @var array<int, \Illuminate\Support\Collection<int, Deliverable>> */
    private array $commitmentDeliverables = [];

    /**
     * Import all rows atomically — any failure rolls back every DB change.
     *
     * @throws Throwable
     */
    public function import(array $preview, array $meta): array
    {
        return DB::transaction(function () use ($preview, $meta) {
            $framework = Framework::query()->findOrFail($meta['framework_id']);
            $year = (int) $framework->year;

            $totals = $this->emptyStats();
            $sectorBundles = $this->sectorBundlesFromPreview($preview, $meta);

            foreach ($sectorBundles as $bundle) {
                $sectorStats = $this->importSectorRows(
                    (int) $bundle['sector_id'],
                    (int) $framework->id,
                    $year,
                    $bundle['rows'] ?? [],
                    (bool) ($meta['include_actuals'] ?? false),
                );

                foreach ($sectorStats as $key => $value) {
                    $totals[$key] = ($totals[$key] ?? 0) + $value;
                }
            }

            $totals['sectors_processed'] = count($sectorBundles);

            return $totals;
        });
    }

    /**
     * @return array{sector_id:int, rows:array<int, array>}[]
     */
    private function sectorBundlesFromPreview(array $preview, array $meta): array
    {
        if (!empty($preview['sectors'])) {
            return collect($preview['sectors'])
                ->map(fn (array $sectorPreview) => [
                    'sector_id' => (int) ($sectorPreview['sector_id'] ?? 0),
                    'rows' => $sectorPreview['rows'] ?? [],
                ])
                ->filter(fn (array $bundle) => $bundle['sector_id'] > 0)
                ->values()
                ->all();
        }

        return [[
            'sector_id' => (int) $meta['sector_id'],
            'rows' => $preview['rows'] ?? [],
        ]];
    }

    private function emptyStats(): array
    {
        return [
            'commitments_created' => 0,
            'commitments_matched' => 0,
            'deliverables_created' => 0,
            'deliverables_matched' => 0,
            'kpis_created' => 0,
            'kpis_matched' => 0,
            'targets_created' => 0,
            'targets_updated' => 0,
            'milestones_created' => 0,
            'milestones_updated' => 0,
            'milestones_unchanged' => 0,
            'milestones_skipped' => 0,
            'targets_unchanged' => 0,
            'actuals_updated' => 0,
            'actuals_submitted' => 0,
            'actuals_unchanged' => 0,
            'actuals_skipped_locked' => 0,
            'rows_processed' => 0,
            'sectors_processed' => 0,
        ];
    }

    private function importSectorRows(
        int $sectorId,
        int $frameworkId,
        int $year,
        array $rows,
        bool $includeActuals = false,
    ): array {
        $this->commitmentCache = [];
        $this->deliverableCache = [];
        $this->kpiCache = [];
        $this->sectorCommitments = null;
        $this->commitmentDeliverables = [];

        Sector::query()->lockForUpdate()->findOrFail($sectorId);

        $stats = $this->emptyStats();

        foreach ($rows as $row) {
            if (trim($row['deliverable'] ?? '') === '' && trim($row['kpi'] ?? '') === '') {
                continue;
            }

            $stats['rows_processed']++;

            $commitmentResult = $this->resolveCommitment(
                $sectorId,
                $frameworkId,
                $row['commitment'] ?? ''
            );
            $stats[$commitmentResult['created'] ? 'commitments_created' : 'commitments_matched']++;

            $deliverableResult = $this->resolveDeliverable(
                $commitmentResult['model'],
                $frameworkId,
                $year,
                $row['deliverable'] ?? ''
            );
            $stats[$deliverableResult['created'] ? 'deliverables_created' : 'deliverables_matched']++;

            $kpiResult = $this->resolveKpi(
                $deliverableResult['model'],
                $frameworkId,
                $year,
                $row
            );
            $stats[$kpiResult['created'] ? 'kpis_created' : 'kpis_matched']++;

            $annualTarget = $this->resolveAnnualTargetValue($row);
            if ($annualTarget !== null) {
                $targetResult = $this->upsertKpiTarget($kpiResult['model'], $year, $annualTarget);
                if ($targetResult['created']) {
                    $stats['targets_created']++;
                } elseif ($targetResult['updated']) {
                    $stats['targets_updated']++;
                } else {
                    $stats['targets_unchanged']++;
                }
            }

            $milestones = $this->quarterMilestones($row);
            $actuals = $includeActuals ? $this->quarterActuals($row) : [];

            foreach ([1, 2, 3, 4] as $quarter) {
                $milestone = $milestones[$quarter] ?? null;
                $actual = $actuals[$quarter] ?? null;

                if ($milestone === null && $actual === null) {
                    continue;
                }

                if ($milestone !== null) {
                    $milestoneResult = $this->upsertMilestone(
                        $kpiResult['model'],
                        $frameworkId,
                        $year,
                        $quarter,
                        $milestone
                    );
                    $stats[$milestoneResult['stat']]++;
                }

                if ($includeActuals && $actual !== null) {
                    $actualResult = $this->upsertActual(
                        $kpiResult['model'],
                        $frameworkId,
                        $year,
                        $quarter,
                        $actual,
                        trim($row['remarks'] ?? ''),
                    );
                    $stats[$actualResult['stat']]++;
                }
            }
        }

        return $stats;
    }

    private function resolveCommitment(int $sectorId, int $frameworkId, string $name): array
    {
        $cacheKey = $sectorId . ':' . $frameworkId . ':' . BulkUploadLabelMatcher::normalizeKey($name);

        if (isset($this->commitmentCache[$cacheKey])) {
            return ['model' => $this->commitmentCache[$cacheKey], 'created' => false];
        }

        $existing = $this->sectorCommitments($sectorId, $frameworkId)
            ->first(fn (Commitment $commitment) => BulkUploadLabelMatcher::labelsAreEquivalent($commitment->name, $name));

        if ($existing) {
            $this->commitmentCache[$cacheKey] = $existing;

            return ['model' => $existing, 'created' => false];
        }

        $commitment = Commitment::create([
            'sector_id' => $sectorId,
            'framework_id' => $frameworkId,
            'name' => trim($name),
            'type' => 'Result Framework',
            'description' => trim($name),
            'status' => 'In Progress',
        ]);

        $this->sectorCommitments($sectorId, $frameworkId)->push($commitment);
        $this->commitmentCache[$cacheKey] = $commitment;

        return ['model' => $commitment, 'created' => true];
    }

    private function sectorCommitments(int $sectorId, int $frameworkId)
    {
        if ($this->sectorCommitments === null) {
            $this->sectorCommitments = Commitment::query()
                ->where('sector_id', $sectorId)
                ->where('framework_id', $frameworkId)
                ->get();
        }

        return $this->sectorCommitments;
    }

    private function deliverablesForCommitment(int $commitmentId, int $frameworkId)
    {
        if (!isset($this->commitmentDeliverables[$commitmentId])) {
            $this->commitmentDeliverables[$commitmentId] = Deliverable::query()
                ->where('commitment_id', $commitmentId)
                ->where('framework_id', $frameworkId)
                ->get();
        }

        return $this->commitmentDeliverables[$commitmentId];
    }

    private function resolveDeliverable(Commitment $commitment, int $frameworkId, int $year, string $name): array
    {
        $cacheKey = $commitment->id . ':' . BulkUploadLabelMatcher::normalizeKey($name);

        if (isset($this->deliverableCache[$cacheKey])) {
            return ['model' => $this->deliverableCache[$cacheKey], 'created' => false];
        }

        $normalized = BulkUploadLabelMatcher::normalizeKey($name);

        $existing = $this->deliverablesForCommitment($commitment->id, $frameworkId)
            ->first(fn (Deliverable $deliverable) => BulkUploadLabelMatcher::labelsAreEquivalent($deliverable->deliverable, $name));

        if ($existing) {
            $this->deliverableCache[$cacheKey] = $existing;

            return ['model' => $existing, 'created' => false];
        }

        $deliverable = Deliverable::create([
            'commitment_id' => $commitment->id,
            'framework_id' => $frameworkId,
            'deliverable' => trim($name),
            'start_date' => sprintf('%d-01-01', $year),
            'end_date' => sprintf('%d-12-31', $year),
            'status' => 'In Progress',
        ]);

        $this->deliverablesForCommitment($commitment->id, $frameworkId)->push($deliverable);

        $this->deliverableCache[$cacheKey] = $deliverable;

        return ['model' => $deliverable, 'created' => true];
    }

    private function resolveKpi(Deliverable $deliverable, int $frameworkId, int $year, array $row): array
    {
        $kpiName = trim($row['kpi'] ?? '');
        $cacheKey = $deliverable->id . ':' . $year . ':' . BulkUploadLabelMatcher::normalizeKey($kpiName);

        if (isset($this->kpiCache[$cacheKey])) {
            return ['model' => $this->kpiCache[$cacheKey], 'created' => false];
        }

        $existing = Kpi::query()
            ->where('deliverable_id', $deliverable->id)
            ->where('framework_id', $frameworkId)
            ->where('year', $year)
            ->get()
            ->first(fn (Kpi $kpi) => BulkUploadLabelMatcher::labelsAreEquivalent($kpi->kpi, $kpiName));

        if ($existing) {
            $updates = [];
            $baseline = $this->parseNumeric($row['baseline'] ?? '');
            // Re-upload policy: overwrite unlocked structural fields when the sheet supplies a value.
            if ($baseline !== null) {
                $updates['target_value'] = (string) $baseline;
            }
            if (!empty($updates)) {
                $existing->update($updates);
            }

            $this->kpiCache[$cacheKey] = $existing;

            return ['model' => $existing, 'created' => false];
        }

        $kpi = Kpi::create([
            'deliverable_id' => $deliverable->id,
            'framework_id' => $frameworkId,
            'kpi' => $kpiName,
            'target_value' => (string) ($this->parseNumeric($row['baseline'] ?? '') ?? 0),
            'unit_of_measurement' => $this->inferUnit($kpiName, $row['baseline'] ?? '', $row['target'] ?? ''),
            'year' => $year,
        ]);

        $this->kpiCache[$cacheKey] = $kpi;

        return ['model' => $kpi, 'created' => true];
    }

    private function upsertKpiTarget(Kpi $kpi, int $year, float $target): array
    {
        $existing = KpiTarget::query()
            ->where('kpi_id', $kpi->id)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($existing) {
            $changed = (float) $existing->target !== $target;
            $existing->target = $target;
            $existing->save();

            return ['created' => false, 'updated' => $changed];
        }

        KpiTarget::create([
            'kpi_id' => $kpi->id,
            'year' => $year,
            'target' => $target,
        ]);

        return ['created' => true, 'updated' => false];
    }

    private function upsertMilestone(Kpi $kpi, int $frameworkId, int $year, int $quarter, float $milestone): array
    {
        $existing = PerformanceTracking::query()
            ->where('kpi_id', $kpi->id)
            ->where('quarter', $quarter)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($existing) {
            // Re-upload policy: overwrite only when unlocked. Never modify actuals from structure import.
            if ($existing->isLockedFromSectorModification()) {
                return ['stat' => 'milestones_skipped'];
            }

            $changed = (float) ($existing->milestone ?? 0) !== $milestone
                || (int) ($existing->framework_id ?? 0) !== $frameworkId;

            $existing->milestone = $milestone;
            $existing->framework_id = $frameworkId;
            if (!$existing->confirmation_status) {
                $existing->confirmation_status = 'Not Confirmed';
            }
            $existing->save();

            return ['stat' => $changed ? 'milestones_updated' : 'milestones_unchanged'];
        }

        PerformanceTracking::create([
            'kpi_id' => $kpi->id,
            'framework_id' => $frameworkId,
            'quarter' => $quarter,
            'year' => $year,
            'milestone' => $milestone,
            'confirmation_status' => 'Not Confirmed',
        ]);

        return ['stat' => 'milestones_created'];
    }

    private function upsertActual(
        Kpi $kpi,
        int $frameworkId,
        int $year,
        int $quarter,
        float $actual,
        string $remarks = '',
    ): array {
        $existing = PerformanceTracking::query()
            ->where('kpi_id', $kpi->id)
            ->where('quarter', $quarter)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($existing) {
            if ($existing->isLockedFromSectorModification()) {
                return ['stat' => 'actuals_skipped_locked'];
            }

            if ($existing->sector_head_approved_by && $existing->facilitator_decision !== 'Reject') {
                return ['stat' => 'actuals_skipped_locked'];
            }

            $hadActual = $existing->actual_value !== null && (float) $existing->actual_value != 0;
            $sameActual = $existing->actual_value !== null && (float) $existing->actual_value === $actual;
            $sameRemarks = $remarks === '' || $remarks === trim((string) ($existing->remarks ?? ''));

            if ($sameActual && $sameRemarks) {
                return ['stat' => 'actuals_unchanged'];
            }

            $existing->tracking_date = now()->toDateString();
            $existing->actual_value = $actual;
            $existing->framework_id = $frameworkId;
            if ($remarks !== '') {
                $existing->remarks = $remarks;
            }

            if (!$existing->sector_head_approved_at) {
                $existing->confirmation_status = 'Pending Sector Head Approval';
            }

            $existing->save();

            if (!$existing->sector_head_approved_at) {
                Notification::notifySectorHeadForApproval($existing);
            }

            return ['stat' => $hadActual ? 'actuals_updated' : 'actuals_submitted'];
        }

        $tracking = PerformanceTracking::create([
            'kpi_id' => $kpi->id,
            'framework_id' => $frameworkId,
            'quarter' => $quarter,
            'year' => $year,
            'actual_value' => $actual,
            'remarks' => $remarks !== '' ? $remarks : null,
            'tracking_date' => now()->toDateString(),
            'confirmation_status' => 'Pending Sector Head Approval',
        ]);

        Notification::notifySectorHeadForApproval($tracking);

        return ['stat' => 'actuals_submitted'];
    }

    private function resolveAnnualTargetValue(array $row): ?float
    {
        $fullYear = $this->parseNumeric($row['full_year_target'] ?? '');
        if ($fullYear !== null) {
            return $fullYear;
        }

        return $this->parseNumeric($row['target'] ?? '');
    }

    private function quarterMilestones(array $row): array
    {
        return [
            1 => $this->parseNumeric($row['q1_target'] ?? ''),
            2 => $this->parseNumeric($row['q2_target'] ?? ''),
            3 => $this->parseNumeric($row['q3_target'] ?? ''),
            4 => $this->parseNumeric($row['q4_target'] ?? ''),
        ];
    }

    private function quarterActuals(array $row): array
    {
        return [
            1 => $this->parseNumeric($row['q1_actual'] ?? ''),
            2 => $this->parseNumeric($row['q2_actual'] ?? ''),
            3 => $this->parseNumeric($row['q3_actual'] ?? ''),
            4 => $this->parseNumeric($row['q4_actual'] ?? ''),
        ];
    }

    private function parseNumeric(string $value): ?float
    {
        $normalized = str_replace([',', '%', ' '], '', trim($value));
        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    private function inferUnit(string $kpi, string $baseline, string $target): string
    {
        $haystack = strtolower($kpi . ' ' . $baseline . ' ' . $target);

        if (str_contains($haystack, '%')) {
            return 'Percentage';
        }

        if (preg_match('/no\.?\s*of|number of|count/i', $kpi)) {
            return 'Count';
        }

        return 'Units';
    }
}
