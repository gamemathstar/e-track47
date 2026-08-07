<?php

namespace App\Services;

use App\Models\Framework;
use App\Models\Notification;
use App\Models\PerformanceTracking;
use App\Models\Sector;
use Illuminate\Support\Facades\DB;
use Throwable;

class BulkUploadActualsImporter
{
    private const QUARTER_ACTUAL_FIELDS = [
        1 => 'q1_actual',
        2 => 'q2_actual',
        3 => 'q3_actual',
        4 => 'q4_actual',
    ];

    /**
     * @throws Throwable
     */
    public function import(array $preview, array $meta): array
    {
        $sector = Sector::query()->findOrFail($meta['sector_id']);
        $framework = Framework::query()->findOrFail($meta['framework_id']);
        $year = (int) $framework->year;
        $reportingQuarter = isset($meta['reporting_quarter']) ? (int) $meta['reporting_quarter'] : null;

        $resolver = new BulkUploadKpiResolver($sector->id, $framework->id, $year);
        $trackingDate = now()->toDateString();

        $stats = [
            'rows_processed' => 0,
            'actuals_updated' => 0,
            'actuals_submitted' => 0,
            'skipped_no_kpi' => 0,
            'skipped_no_milestone' => 0,
            'skipped_locked' => 0,
            'skipped_no_actual' => 0,
        ];

        return DB::transaction(function () use ($preview, $meta, $resolver, $year, $reportingQuarter, $trackingDate, &$stats) {
            Sector::query()->whereKey($meta['sector_id'])->lockForUpdate()->first();

            foreach ($preview['rows'] ?? [] as $row) {
                if (trim($row['deliverable'] ?? '') === '' && trim($row['kpi'] ?? '') === '') {
                    continue;
                }

                $stats['rows_processed']++;

                $kpi = $resolver->resolve(
                    $row['commitment'] ?? '',
                    $row['deliverable'] ?? '',
                    $row['kpi'] ?? '',
                );

                if (!$kpi) {
                    $stats['skipped_no_kpi']++;
                    continue;
                }

                $hadQuarterActual = false;

                foreach (self::QUARTER_ACTUAL_FIELDS as $quarter => $field) {
                    if ($reportingQuarter !== null && $quarter !== $reportingQuarter) {
                        continue;
                    }

                    $actual = $this->parseNumeric($row[$field] ?? '');
                    if ($actual === null) {
                        continue;
                    }

                    $hadQuarterActual = true;

                    $tracking = PerformanceTracking::query()
                        ->where('kpi_id', $kpi->id)
                        ->where('quarter', $quarter)
                        ->where('year', $year)
                        ->lockForUpdate()
                        ->first();

                    if (!$tracking) {
                        $stats['skipped_no_milestone']++;
                        continue;
                    }

                    if ($tracking->isLockedFromSectorModification()) {
                        $stats['skipped_locked']++;
                        continue;
                    }

                    if ($tracking->sector_head_approved_by && $tracking->facilitator_decision !== 'Reject') {
                        $stats['skipped_locked']++;
                        continue;
                    }

                    $hadActual = $tracking->actual_value !== null && (float) $tracking->actual_value != 0;

                    $tracking->tracking_date = $trackingDate;
                    $tracking->actual_value = $actual;
                    $tracking->remarks = trim($row['remarks'] ?? '') ?: $tracking->remarks;

                    if ($tracking->facilitator_decision === 'Reject') {
                        $tracking->facilitator_decision = null;
                        $tracking->facilitator_rejection_reason = null;
                        $tracking->facilitator_confirmed_at = null;
                        $tracking->facilitator_confirmed_by = null;
                    }

                    if ($tracking->coordinator_decision === 'Reject') {
                        $tracking->coordinator_decision = null;
                        $tracking->coordinator_rejection_reason = null;
                        $tracking->coordinator_confirmed_at = null;
                        $tracking->coordinator_confirmed_by = null;
                        $tracking->facilitator_decision = null;
                        $tracking->facilitator_rejection_reason = null;
                        $tracking->facilitator_confirmed_at = null;
                        $tracking->facilitator_confirmed_by = null;
                        $tracking->delivery_department_value = null;
                        $tracking->delivery_department_remark = null;
                        if ($tracking->sector_head_approved_by) {
                            $tracking->confirmation_status = 'Pending Facilitator';
                        }
                    }

                    if (!$tracking->sector_head_approved_at) {
                        $tracking->confirmation_status = 'Pending Sector Head Approval';
                    }

                    $tracking->save();

                    if (!$tracking->sector_head_approved_at) {
                        Notification::notifySectorHeadForApproval($tracking);
                    }

                    $stats['actuals_updated']++;
                    if (!$hadActual) {
                        $stats['actuals_submitted']++;
                    }
                }

                if (!$hadQuarterActual && $reportingQuarter !== null) {
                    $stats['skipped_no_actual']++;
                }
            }

            return $stats;
        });
    }

    private function parseNumeric(string $value): ?float
    {
        $normalized = str_replace([',', '%', ' '], '', trim($value));
        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }
}
