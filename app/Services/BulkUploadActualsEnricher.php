<?php

namespace App\Services;

use App\Models\PerformanceTracking;

class BulkUploadActualsEnricher
{
    private const QUARTER_ACTUAL_FIELDS = [
        1 => 'q1_actual',
        2 => 'q2_actual',
        3 => 'q3_actual',
        4 => 'q4_actual',
    ];

    public function enrich(array $preview, array $meta): array
    {
        $resolver = new BulkUploadKpiResolver(
            (int) $meta['sector_id'],
            (int) $meta['framework_id'],
            (int) $meta['framework_year'],
        );

        $reportingQuarter = isset($meta['reporting_quarter']) ? (int) $meta['reporting_quarter'] : null;
        $warnings = $preview['warnings'] ?? [];

        $rows = collect($preview['rows'] ?? [])->map(function (array $row) use ($resolver, $reportingQuarter, &$warnings) {
            $kpi = $resolver->resolve(
                $row['commitment'] ?? '',
                $row['deliverable'] ?? '',
                $row['kpi'] ?? '',
            );

            $row['kpi_id'] = $kpi?->id;
            $row['quarter_updates'] = [];

            if (!$kpi) {
                $warnings[] = [
                    'row' => $row['sn'] ?? 0,
                    'message' => 'No matching KPI found in this sector. Confirm PDCU has set up the framework structure first.',
                ];

                return $row;
            }

            foreach (self::QUARTER_ACTUAL_FIELDS as $quarter => $field) {
                if ($reportingQuarter !== null && $quarter !== $reportingQuarter) {
                    continue;
                }

                $actual = $this->parseNumeric($row[$field] ?? '');
                if ($actual === null) {
                    continue;
                }

                $tracking = PerformanceTracking::query()
                    ->where('kpi_id', $kpi->id)
                    ->where('quarter', $quarter)
                    ->where('year', (int) $meta['framework_year'])
                    ->first();

                $update = [
                    'quarter' => $quarter,
                    'actual' => $actual,
                    'tracking_id' => $tracking?->id,
                    'milestone' => $tracking?->milestone,
                    'status' => 'ready',
                    'message' => null,
                ];

                if (!$tracking) {
                    $update['status'] = 'skipped';
                    $update['message'] = "No Q{$quarter} milestone exists for this KPI.";
                } elseif ($tracking->isLockedFromSectorModification()) {
                    $update['status'] = 'locked';
                    $update['message'] = 'Record confirmed by PDCU and cannot be modified.';
                } elseif ($tracking->sector_head_approved_by && $tracking->facilitator_decision !== 'Reject') {
                    $update['status'] = 'locked';
                    $update['message'] = 'Record approved by Sector Head and cannot be modified.';
                }

                if ($update['status'] !== 'ready') {
                    $warnings[] = [
                        'row' => $row['sn'] ?? 0,
                        'message' => $row['kpi'] . " (Q{$quarter}): " . $update['message'],
                    ];
                }

                $row['quarter_updates'][] = $update;
            }

            if ($kpi && empty($row['quarter_updates']) && $reportingQuarter !== null) {
                $warnings[] = [
                    'row' => $row['sn'] ?? 0,
                    'message' => 'No actual value provided for the selected reporting quarter.',
                ];
            }

            return $row;
        })->all();

        $preview['rows'] = $rows;
        $preview['warnings'] = $warnings;
        $preview['summary']['warnings'] = count($warnings);
        $preview['summary']['actual_updates'] = collect($rows)
            ->flatMap(fn ($row) => $row['quarter_updates'] ?? [])
            ->where('status', 'ready')
            ->count();

        return $preview;
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
