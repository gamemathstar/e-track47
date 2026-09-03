<?php

namespace App\Services;

use App\Models\PerformanceTracking;

class BulkUploadStructureEnricher
{
    private const QUARTER_MILESTONE_FIELDS = [
        1 => 'q1_target',
        2 => 'q2_target',
        3 => 'q3_target',
        4 => 'q4_target',
    ];

    private const QUARTER_ACTUAL_FIELDS = [
        1 => 'q1_actual',
        2 => 'q2_actual',
        3 => 'q3_actual',
        4 => 'q4_actual',
    ];

    public function enrich(array $preview, array $meta): array
    {
        $warnings = $preview['warnings'] ?? [];
        $matched = 0;
        $createdEstimate = 0;
        $lockedMilestones = 0;
        $lockedActuals = 0;
        $actualValuesFound = 0;
        $includeActuals = (bool) ($meta['include_actuals'] ?? false);
        $pdcuConfirmOverride = $includeActuals && (bool) ($meta['pdcu_confirm_override'] ?? false);

        $sectorBundles = !empty($preview['sectors'])
            ? $preview['sectors']
            : [[
                'sector_id' => (int) ($meta['sector_id'] ?? 0),
                'sector_name' => $meta['sector_name'] ?? 'Sector',
                'rows' => $preview['rows'] ?? [],
            ]];

        foreach ($sectorBundles as $bundle) {
            $sectorId = (int) ($bundle['sector_id'] ?? 0);
            if ($sectorId <= 0) {
                continue;
            }

            $resolver = new BulkUploadKpiResolver(
                $sectorId,
                (int) $meta['framework_id'],
                (int) $meta['framework_year'],
            );

            foreach ($bundle['rows'] ?? [] as $row) {
                if (trim($row['deliverable'] ?? '') === '' && trim($row['kpi'] ?? '') === '') {
                    continue;
                }

                $kpi = $resolver->resolve(
                    $row['commitment'] ?? '',
                    $row['deliverable'] ?? '',
                    $row['kpi'] ?? '',
                );

                if (!$kpi) {
                    $createdEstimate++;
                    continue;
                }

                $matched++;

                foreach (self::QUARTER_MILESTONE_FIELDS as $quarter => $field) {
                    $milestone = $this->parseNumeric($row[$field] ?? '');
                    if ($milestone === null) {
                        continue;
                    }

                    $tracking = PerformanceTracking::query()
                        ->where('kpi_id', $kpi->id)
                        ->where('quarter', $quarter)
                        ->where('year', (int) $meta['framework_year'])
                        ->first();

                    if ($tracking && $tracking->isLockedFromSectorModification()) {
                        $lockedMilestones++;
                        $warnings[] = [
                            'row' => $row['sn'] ?? 0,
                            'message' => ($bundle['sector_name'] ?? 'Sector') . ': '
                                . ($row['kpi'] ?? 'KPI')
                                . " (Q{$quarter}) is coordinator-confirmed and will not be overwritten.",
                            'sector_id' => $sectorId,
                        ];
                    }
                }

                if ($includeActuals) {
                    foreach (self::QUARTER_ACTUAL_FIELDS as $quarter => $field) {
                        $actual = $this->parseNumeric($row[$field] ?? '');
                        if ($actual === null) {
                            continue;
                        }

                        $actualValuesFound++;

                        $tracking = PerformanceTracking::query()
                            ->where('kpi_id', $kpi->id)
                            ->where('quarter', $quarter)
                            ->where('year', (int) $meta['framework_year'])
                            ->first();

                        if (
                            $tracking
                            && $tracking->isLockedFromSectorModification()
                        ) {
                            $lockedActuals++;
                            $warnings[] = [
                                'row' => $row['sn'] ?? 0,
                                'message' => ($bundle['sector_name'] ?? 'Sector') . ': '
                                    . ($row['kpi'] ?? 'KPI')
                                    . " (Q{$quarter} actual) is coordinator-confirmed and will not be overwritten.",
                                'sector_id' => $sectorId,
                            ];
                        } elseif (
                            !$pdcuConfirmOverride
                            && $tracking
                            && $tracking->sector_head_approved_by
                            && $tracking->facilitator_decision !== 'Reject'
                        ) {
                            $lockedActuals++;
                            $warnings[] = [
                                'row' => $row['sn'] ?? 0,
                                'message' => ($bundle['sector_name'] ?? 'Sector') . ': '
                                    . ($row['kpi'] ?? 'KPI')
                                    . " (Q{$quarter} actual) is locked/approved and will not be overwritten.",
                                'sector_id' => $sectorId,
                            ];
                        }
                    }
                } else {
                    foreach (self::QUARTER_ACTUAL_FIELDS as $field) {
                        if ($this->parseNumeric($row[$field] ?? '') !== null) {
                            $actualValuesFound++;
                        }
                    }
                }
            }
        }

        if (!$includeActuals && $actualValuesFound > 0) {
            $warnings[] = [
                'row' => 0,
                'message' => "{$actualValuesFound} actual value(s) were found in the file but will be ignored. Enable “Include actual values” to import them.",
            ];
        }

        if ($pdcuConfirmOverride && $actualValuesFound > 0) {
            $warnings[] = [
                'row' => 0,
                'message' => 'PDCU confirm override is enabled: imported actuals will be stamped Sector Head → Facilitator → Coordinator approved and locked. Already coordinator-confirmed rows remain skipped.',
            ];
        }

        $preview['warnings'] = $warnings;
        $preview['summary']['warnings'] = count($warnings);
        $preview['summary']['matched_kpis'] = $matched;
        $preview['summary']['new_structure_estimate'] = $createdEstimate;
        $preview['summary']['locked_milestones'] = $lockedMilestones;
        $preview['summary']['locked_actuals'] = $lockedActuals;
        $preview['summary']['actual_values_found'] = $actualValuesFound;
        $preview['summary']['include_actuals'] = $includeActuals;
        $preview['summary']['pdcu_confirm_override'] = $pdcuConfirmOverride;
        $preview['summary']['reupload_policy'] = 'overwrite_unlocked';

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
