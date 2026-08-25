<?php

namespace App\Services;

use App\Models\Framework;
use App\Models\Sector;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BulkUploadStructureTemplateExporter
{
    public const SECTOR_ID_CELL = 'R1';

    /**
     * @param  Collection<int, Sector>|array<int, Sector>  $sectors
     */
    public function download(Framework $framework, Collection|array $sectors): BinaryFileResponse
    {
        $sectors = collect($sectors)->values();
        $path = $this->build($framework, $sectors);

        $filename = $sectors->count() === 1
            ? sprintf('bulk-structure-upload-%s-%s.xlsx', $sectors->first()->id, $framework->year)
            : sprintf('bulk-structure-upload-multi-%s.xlsx', $framework->year);

        return response()->download(
            $path,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    /**
     * @param  Collection<int, Sector>  $sectors
     */
    public function build(Framework $framework, Collection $sectors): string
    {
        $baseTemplate = resource_path('templates/bulk-performance-upload-template.xlsx');
        if (!is_file($baseTemplate)) {
            throw new \RuntimeException('Bulk upload template is not available.');
        }

        $baseSheet = IOFactory::load($baseTemplate)->getActiveSheet();
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $usedTitles = [];

        foreach ($sectors as $index => $sector) {
            $sheet = new Worksheet($spreadsheet, $this->uniqueSheetTitle($sector->sector_name, $usedTitles));
            $spreadsheet->addSheet($sheet, $index);

            $this->copyTemplateSheet($baseSheet, $sheet);
            $sheet->setCellValue('A1', strtoupper($sector->sector_name));
            $sheet->setCellValue('A2', 'ANNUAL DELIVERY PERFORMANCE MANAGEMENT FRAMEWORK');
            $sheet->setCellValue(
                'A3',
                $framework->year . ' (JANUARY - DECEMBER, ' . $framework->year . ') PERFORMANCE ASSESSMENT'
            );
            $sheet->setCellValue(self::SECTOR_ID_CELL, 'SECTOR_ID:' . $sector->id);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $tempPath = storage_path('app/temp/bulk-structure-' . uniqid('', true) . '.xlsx');
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        (new Xlsx($spreadsheet))->save($tempPath);

        return $tempPath;
    }

    private function copyTemplateSheet(Worksheet $source, Worksheet $target): void
    {
        $highestRow = min(12, (int) $source->getHighestDataRow());
        $highestColumn = $source->getHighestDataColumn();

        for ($row = 1; $row <= $highestRow; $row++) {
            foreach (range('A', $highestColumn) as $column) {
                $sourceCell = $column . $row;
                $target->setCellValue($sourceCell, $source->getCell($sourceCell)->getValue());
                $target->getStyle($sourceCell)->applyFromArray(
                    $source->getStyle($sourceCell)->exportArray()
                );
            }
        }

        foreach (range('A', $highestColumn) as $column) {
            $width = $source->getColumnDimension($column)->getWidth();
            if ($width > 0) {
                $target->getColumnDimension($column)->setWidth($width);
            }
        }

        foreach ($source->getMergeCells() as $mergeRange) {
            if (!preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/', $mergeRange, $matches)) {
                continue;
            }

            $rowStart = (int) $matches[2];
            $rowEnd = (int) $matches[4];
            if ($rowEnd < 1 || $rowStart > $highestRow) {
                continue;
            }

            $target->mergeCells(
                $matches[1] . max(1, $rowStart) . ':' . $matches[3] . min($highestRow, $rowEnd)
            );
        }
    }

    /**
     * @param  array<string, true>  $usedTitles
     */
    private function uniqueSheetTitle(string $sectorName, array &$usedTitles): string
    {
        $base = preg_replace('/[\\\\\\/?\\*\\[\\]:]/', '', $sectorName) ?? 'Sector';
        $base = trim($base);
        if ($base === '') {
            $base = 'Sector';
        }

        $base = mb_substr($base, 0, 31);
        $title = $base;
        $suffix = 2;

        while (isset($usedTitles[strtolower($title)])) {
            $suffixText = ' (' . $suffix . ')';
            $title = mb_substr($base, 0, 31 - strlen($suffixText)) . $suffixText;
            $suffix++;
        }

        $usedTitles[strtolower($title)] = true;

        return $title;
    }
}
