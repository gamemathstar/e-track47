<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BulkUploadParser
{
    private const LAST_COLUMN = 'Q';

    public function parse(UploadedFile $file, bool $forPdcu = true): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'csv') {
            return $this->parseCsv($file, $forPdcu);
        }

        return $this->parseSpreadsheet(
            IOFactory::load($file->getPathname())->getActiveSheet(),
            $forPdcu
        );
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
