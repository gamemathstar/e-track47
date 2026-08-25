<?php

namespace App\Services;

use App\Models\Sector;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BulkUploadParser
{
    private const LAST_COLUMN = 'Q';

    /**
     * @param  Collection<int, Sector>|array<int, Sector>|null  $expectedSectors
     */
    public function parse(UploadedFile $file, bool $forPdcu = true, Collection|array|null $expectedSectors = null): array
    {
        $expectedSectors = collect($expectedSectors ?? []);
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'csv') {
            $preview = $this->parseCsv($file, $forPdcu);

            return $this->wrapSingleSectorPreview($preview, $expectedSectors->first());
        }

        $spreadsheet = IOFactory::load($file->getPathname());

        if ($expectedSectors->count() > 1 || $spreadsheet->getSheetCount() > 1) {
            return $this->parseMultiSheetWorkbook($spreadsheet, $forPdcu, $expectedSectors);
        }

        $preview = $this->parseSpreadsheet($spreadsheet->getActiveSheet(), $forPdcu);
        $sector = $expectedSectors->first()
            ?? $this->resolveSectorFromSheet($spreadsheet->getActiveSheet(), $expectedSectors);

        return $this->wrapSingleSectorPreview($preview, $sector);
    }

    /**
     * @param  Collection<int, Sector>  $expectedSectors
     */
    private function parseMultiSheetWorkbook(Spreadsheet $spreadsheet, bool $forPdcu, Collection $expectedSectors): array
    {
        $sectorPreviews = [];
        $unmatchedSheets = [];
        $matchedSectorIds = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sector = $this->resolveSectorFromSheet($sheet, $expectedSectors);
            if (!$sector) {
                $unmatchedSheets[] = $sheet->getTitle();
                continue;
            }

            if (isset($matchedSectorIds[$sector->id])) {
                continue;
            }

            $sheetPreview = $this->parseSpreadsheet($sheet, $forPdcu);
            if (($sheetPreview['summary']['total_records'] ?? 0) === 0) {
                continue;
            }

            $matchedSectorIds[$sector->id] = true;
            $sectorPreviews[] = $this->attachSectorContext($sheetPreview, $sector, $sheet->getTitle());
        }

        if ($sectorPreviews === []) {
            throw new \RuntimeException('No matching sector sheets with performance records were found in the uploaded workbook.');
        }

        return $this->aggregateSectorPreviews($sectorPreviews, $unmatchedSheets);
    }

    /**
     * @param  Collection<int, Sector>  $expectedSectors
     */
    private function resolveSectorFromSheet(Worksheet $sheet, Collection $expectedSectors): ?Sector
    {
        $marker = trim((string) $sheet->getCell(BulkUploadStructureTemplateExporter::SECTOR_ID_CELL)->getValue());
        if (preg_match('/^SECTOR_ID:(\d+)$/i', $marker, $matches)) {
            $sectorId = (int) $matches[1];
            $fromExpected = $expectedSectors->first(fn (Sector $sector) => (int) $sector->id === $sectorId);
            if ($fromExpected) {
                return $fromExpected;
            }

            return Sector::query()->find($sectorId);
        }

        if ($expectedSectors->isEmpty()) {
            return null;
        }

        $candidates = array_filter([
            $sheet->getTitle(),
            trim((string) $sheet->getCell('A1')->getFormattedValue()),
        ]);

        foreach ($expectedSectors as $sector) {
            foreach ($candidates as $candidate) {
                if (BulkUploadLabelMatcher::labelsAreEquivalent($sector->sector_name, $candidate)) {
                    return $sector;
                }
            }
        }

        return null;
    }

    private function wrapSingleSectorPreview(array $preview, ?Sector $sector): array
    {
        if (!$sector) {
            $preview['multi_sector'] = false;
            $preview['sectors'] = [];

            return $preview;
        }

        $sectorPreview = $this->attachSectorContext($preview, $sector, $sector->sector_name);

        return $this->aggregateSectorPreviews([$sectorPreview], []);
    }

    private function attachSectorContext(array $preview, Sector $sector, string $sheetName): array
    {
        $sectorId = (int) $sector->id;
        $sectorName = $sector->sector_name;

        $decorate = function (array $row) use ($sectorId, $sectorName): array {
            $row['sector_id'] = $sectorId;
            $row['sector_name'] = $sectorName;

            return $row;
        };

        $preview['sector_id'] = $sectorId;
        $preview['sector_name'] = $sectorName;
        $preview['sheet_name'] = $sheetName;
        $preview['rows'] = array_map($decorate, $preview['rows'] ?? []);
        $preview['deliverables'] = array_map($decorate, $preview['deliverables'] ?? []);
        $preview['kpis'] = array_map($decorate, $preview['kpis'] ?? []);
        $preview['commitments'] = array_map(function (array $commitment) use ($decorate) {
            $commitment['rows'] = array_map($decorate, $commitment['rows'] ?? []);

            return $commitment;
        }, $preview['commitments'] ?? []);

        return $preview;
    }

    /**
     * @param  array<int, array>  $sectorPreviews
     * @param  array<int, string>  $unmatchedSheets
     */
    private function aggregateSectorPreviews(array $sectorPreviews, array $unmatchedSheets): array
    {
        $rows = [];
        $commitments = [];
        $deliverables = [];
        $kpis = [];
        $warnings = [];
        $sn = 0;

        foreach ($sectorPreviews as $sectorPreview) {
            foreach ($sectorPreview['warnings'] ?? [] as $warning) {
                $warnings[] = [
                    'row' => $warning['row'] ?? 0,
                    'message' => ($sectorPreview['sector_name'] ?? 'Sector') . ': ' . ($warning['message'] ?? ''),
                    'sector_id' => $sectorPreview['sector_id'] ?? null,
                ];
            }

            foreach ($sectorPreview['rows'] ?? [] as $row) {
                $sn++;
                $row['sn'] = $sn;
                $rows[] = $row;
            }

            foreach ($sectorPreview['commitments'] ?? [] as $commitment) {
                $commitments[] = $commitment;
            }
            foreach ($sectorPreview['deliverables'] ?? [] as $deliverable) {
                $deliverables[] = $deliverable;
            }
            foreach ($sectorPreview['kpis'] ?? [] as $kpi) {
                $kpis[] = $kpi;
            }
        }

        foreach ($unmatchedSheets as $sheetName) {
            $warnings[] = [
                'row' => 0,
                'message' => "Sheet \"{$sheetName}\" could not be matched to a selected sector and was skipped.",
            ];
        }

        return [
            'multi_sector' => count($sectorPreviews) > 1,
            'sectors' => $sectorPreviews,
            'summary' => [
                'total_records' => count($rows),
                'sectors' => count($sectorPreviews),
                'commitments' => count($commitments),
                'deliverables' => count($deliverables),
                'kpis' => count($kpis),
                'warnings' => count($warnings),
            ],
            'commitments' => $commitments,
            'deliverables' => $deliverables,
            'kpis' => $kpis,
            'warnings' => $warnings,
            'rows' => $rows,
            'unmatched_sheets' => $unmatchedSheets,
        ];
    }

    private function parseCsv(UploadedFile $file, bool $forPdcu): array
    {
        $rows = [];
        if (($handle = fopen($file->getPathname(), 'r')) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }

        return $this->buildPreviewFromRows($rows, $forPdcu);
    }

    private function parseSpreadsheet(Worksheet $sheet, bool $forPdcu): array
    {
        $rows = [];

        foreach ($sheet->getRowIterator() as $row) {
            $rowData = [];
            foreach ($row->getCellIterator('A', self::LAST_COLUMN) as $cell) {
                $rowData[] = trim((string) $cell->getFormattedValue());
            }
            $rows[] = $rowData;
        }

        return $this->buildPreviewFromRows($rows, $forPdcu);
    }

    private function buildPreviewFromRows(array $rows, bool $forPdcu = true): array
    {
        $commitments = [];
        $deliverables = [];
        $kpis = [];
        $warnings = [];
        $currentCommitmentIndex = null;
        $sn = 0;

        foreach ($rows as $row) {
            $colA = $row[0] ?? '';
            $colB = $row[1] ?? '';
            $colC = $row[2] ?? '';
            $colF = $row[5] ?? '';
            $colG = $row[6] ?? '';
            $colH = $row[7] ?? '';
            $colI = $row[8] ?? '';
            $colJ = $row[9] ?? '';
            $colK = $row[10] ?? '';
            $colL = $row[11] ?? '';
            $colM = $row[12] ?? '';
            $colN = $row[13] ?? '';
            $colO = $row[14] ?? '';
            $colP = $row[15] ?? '';
            $colQ = $row[16] ?? '';

            if ($this->isTitleRow($colA, $colB, $colC) || $this->isHeaderRow($colA, $colB, $colC)) {
                continue;
            }

            if ($this->isCommitmentRow($colA, $colB)) {
                $commitments[] = [
                    'title' => $colA,
                    'responsible_unit' => $this->extractResponsibleUnit($colA),
                    'rows' => [],
                ];
                $currentCommitmentIndex = count($commitments) - 1;
                continue;
            }

            if ($currentCommitmentIndex === null || ($colB === '' && $colC === '')) {
                continue;
            }

            if ($colB === '' && $colC !== '') {
                $colB = $deliverables[array_key_last($deliverables)]['deliverable'] ?? '—';
            }

            $sn++;
            $rowWarnings = $this->validateRow($sn, $colB, $colC, $colF, $colG, $colH, $forPdcu);
            $warnings = array_merge($warnings, $rowWarnings);

            $record = [
                'sn' => $sn,
                'commitment' => $commitments[$currentCommitmentIndex]['title'],
                'deliverable' => $colB,
                'kpi' => $colC,
                'result_no' => $row[3] ?? '',
                'baseline' => $row[4] ?? '',
                'target' => $colF !== '' ? $colF : '—',
                'q1_target' => $colG,
                'q1_actual' => $colH,
                'q2_target' => $colI,
                'q2_actual' => $colJ,
                'q3_target' => $colK,
                'q3_actual' => $colL,
                'q4_target' => $colM,
                'q4_actual' => $colN,
                'full_year_target' => $colO,
                'full_year_actual' => $colP,
                'remarks' => $colQ,
                'status' => empty($rowWarnings) ? 'on_track' : 'at_risk',
                'warning' => $rowWarnings[0]['message'] ?? null,
                'quarter_actuals' => $forPdcu
                    ? $this->quarterTargetBars($colF, $colG, $colI, $colK, $colM)
                    : $this->quarterBars($colG, $colH, $colI, $colJ, $colK, $colL, $colM, $colN),
            ];

            $commitments[$currentCommitmentIndex]['rows'][] = $record;
            $deliverables[] = $record;
            if ($colC !== '') {
                $kpis[] = $record;
            }
        }

        $flatRows = collect($commitments)
            ->flatMap(fn ($commitment) => collect($commitment['rows'])->map(function ($row) use ($commitment) {
                $row['responsible_unit'] = $commitment['responsible_unit'];

                return $row;
            }))
            ->values()
            ->all();

        return [
            'summary' => [
                'total_records' => count($flatRows),
                'commitments' => count($commitments),
                'deliverables' => count($deliverables),
                'kpis' => count($kpis),
                'warnings' => count($warnings),
            ],
            'commitments' => $commitments,
            'deliverables' => $deliverables,
            'kpis' => $kpis,
            'warnings' => $warnings,
            'rows' => $flatRows,
        ];
    }

    private function isTitleRow(string $colA, string $colB, string $colC): bool
    {
        if ($colB !== '' || $colC !== '') {
            return false;
        }

        $haystack = strtolower(trim($colA));

        if ($haystack === '') {
            return false;
        }

        return str_contains($haystack, 'ministry')
            || str_contains($haystack, 'annual delivery performance')
            || str_contains($haystack, 'performance management framework')
            || (str_contains($haystack, 'january') && str_contains($haystack, 'december'))
            || preg_match('/^\d{4}\s*\(/', $colA) === 1;
    }

    private function isHeaderRow(string $colA, string $colB, string $colC): bool
    {
        $haystack = strtolower($colA . ' ' . $colB . ' ' . $colC);

        return str_contains($haystack, 'expected outputs')
            || str_contains($haystack, 'output kpis')
            || str_contains($haystack, 'no. of ops')
            || str_contains($haystack, 'annual delivery')
            || str_contains($haystack, 'result framework')
            || ($colA === '' && $colB === '' && in_array(strtolower($colC), ['target', 'actual'], true));
    }

    private function isCommitmentRow(string $colA, string $colB): bool
    {
        return $colB === '' && preg_match('/^commitment\s+\d+/i', $colA);
    }

    private function extractResponsibleUnit(string $commitmentTitle): string
    {
        if (preg_match('/:\s*(.+)$/i', $commitmentTitle, $matches)) {
            return trim($matches[1]);
        }

        return 'Sector Unit';
    }

    private function validateRow(
        int $sn,
        string $deliverable,
        string $kpi,
        string $target,
        string $q1Target,
        string $q1Actual,
        bool $forPdcu,
    ): array {
        $warnings = [];

        if ($deliverable === '' && $kpi === '') {
            return $warnings;
        }

        if ($kpi === '') {
            $warnings[] = [
                'row' => $sn,
                'message' => 'KPI description is missing for a deliverable row.',
            ];
        }

        if ($forPdcu) {
            if ($kpi !== '' && $target === '' && $q1Target === '') {
                $warnings[] = [
                    'row' => $sn,
                    'message' => 'Missing annual or Q1 target for a KPI row.',
                ];
            }

            return $warnings;
        }

        return $warnings;
    }

    private function quarterTargetBars(
        string $annualTarget,
        string $q1Target,
        string $q2Target,
        string $q3Target,
        string $q4Target,
    ): array {
        $annual = $this->numericValue($annualTarget);

        return collect([$q1Target, $q2Target, $q3Target, $q4Target])
            ->map(function (string $quarterTarget) use ($annual) {
                $value = $this->numericValue($quarterTarget);
                if ($value <= 0) {
                    return 0;
                }

                if ($annual <= 0) {
                    return 100;
                }

                return (int) min(100, round(($value / $annual) * 100));
            })
            ->all();
    }

    private function quarterBars(
        string $q1Target,
        string $q1Actual,
        string $q2Target,
        string $q2Actual,
        string $q3Target = '',
        string $q3Actual = '',
        string $q4Target = '',
        string $q4Actual = '',
    ): array {
        return [
            $this->progressPercent($q1Target, $q1Actual),
            $this->progressPercent($q2Target, $q2Actual),
            $this->progressPercent($q3Target, $q3Actual),
            $this->progressPercent($q4Target, $q4Actual),
        ];
    }

    private function progressPercent(string $target, string $actual): int
    {
        $targetValue = $this->numericValue($target);
        $actualValue = $this->numericValue($actual);

        if ($targetValue <= 0) {
            return $actualValue > 0 ? 100 : 0;
        }

        return (int) min(100, round(($actualValue / $targetValue) * 100));
    }

    private function numericValue(string $value): float
    {
        $normalized = str_replace([',', '%', ' '], '', $value);

        return is_numeric($normalized) ? (float) $normalized : 0;
    }
}
