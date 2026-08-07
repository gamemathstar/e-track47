<?php

namespace App\Services;

use App\Models\DataEntryAccess;
use Illuminate\Support\Collection;

class BulkUploadEntryAccess
{
    /**
     * @param  Collection<int, \App\Models\Sector>  $sectors
     * @return array<int, array<int, array<int, bool>>>
     */
    public static function sectorQuarterAccessMap(Collection $sectors, Collection $frameworks, bool $canBypass): array
    {
        $yearsByFrameworkId = $frameworks->pluck('year', 'id');

        $map = [];

        foreach ($sectors as $sector) {
            $year = (int) ($yearsByFrameworkId[$sector->framework_id] ?? 0);
            if ($year === 0) {
                continue;
            }

            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $map[$sector->id][$year][$quarter] = $canBypass
                    || DataEntryAccess::isDataEntryAllowed($sector->id, $year, $quarter);
            }
        }

        return $map;
    }

    /**
     * @return int[]
     */
    public static function quartersWithActualsInPreview(array $preview): array
    {
        $fields = [
            1 => 'q1_actual',
            2 => 'q2_actual',
            3 => 'q3_actual',
            4 => 'q4_actual',
        ];

        $quarters = [];

        foreach ($preview['rows'] ?? [] as $row) {
            foreach ($fields as $quarter => $field) {
                if (self::hasNumericValue($row[$field] ?? '')) {
                    $quarters[$quarter] = true;
                }
            }
        }

        return array_keys($quarters);
    }

    public static function entryDeniedMessage(int $sectorId, int $frameworkYear, int $quarter): string
    {
        $accessRecord = DataEntryAccess::query()
            ->where('sector_id', $sectorId)
            ->where('year', $frameworkYear)
            ->where('quarter', $quarter)
            ->first();

        $deadline = $accessRecord
            ? ($accessRecord->override_deadline ?? $accessRecord->deadline_date)
            : DataEntryAccess::calculateDeadline($frameworkYear, $quarter);

        return "The data entry window for Q{$quarter} {$frameworkYear} is closed"
            . ' (deadline was ' . $deadline->format('M d, Y') . ').'
            . ' Please contact the PDCU Coordinator to request an extension.';
    }

    /**
     * @return string|null Error message when entry is not allowed.
     */
    public static function validateActualsEntry(
        int $sectorId,
        int $frameworkYear,
        ?int $reportingQuarter,
        array $preview,
        bool $canBypass,
    ): ?string {
        if ($canBypass) {
            return null;
        }

        $quartersToCheck = $reportingQuarter !== null
            ? [$reportingQuarter]
            : self::quartersWithActualsInPreview($preview);

        if ($quartersToCheck === []) {
            return 'No quarterly actual values were found in the uploaded file.';
        }

        foreach ($quartersToCheck as $quarter) {
            if (!DataEntryAccess::isDataEntryAllowed($sectorId, $frameworkYear, $quarter)) {
                return self::entryDeniedMessage($sectorId, $frameworkYear, $quarter);
            }
        }

        return null;
    }

    private static function hasNumericValue(string $value): bool
    {
        $normalized = str_replace([',', '%', ' '], '', trim($value));

        return $normalized !== '' && is_numeric($normalized);
    }
}
