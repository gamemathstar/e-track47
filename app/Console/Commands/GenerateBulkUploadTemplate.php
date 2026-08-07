<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GenerateBulkUploadTemplate extends Command
{
    protected $signature = 'bulk-upload:generate-template
                            {--source=2026 Result Framework_Final.xlsx : Source workbook in project root}
                            {--output=resources/templates/bulk-performance-upload-template.xlsx : Output template path}';

    protected $description = 'Generate the bulk performance upload Excel template from the framework workbook';

    public function handle(): int
    {
        $sourcePath = base_path($this->option('source'));
        $outputPath = base_path($this->option('output'));

        if (!is_file($sourcePath)) {
            $this->error("Source file not found: {$sourcePath}");

            return self::FAILURE;
        }

        $sourceSheet = IOFactory::load($sourcePath)->getSheet(0);

        // Include sector title rows (1–3) plus column headers and sample data rows.
        $startRow = 1;
        $endRow = 12;
        $maxColumn = $sourceSheet->getHighestDataColumn();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');

        $targetRow = 1;
        for ($row = $startRow; $row <= $endRow; $row++) {
            foreach (range('A', $maxColumn) as $column) {
                $sourceCell = $column . $row;
                $targetCell = $column . $targetRow;

                $sheet->setCellValue($targetCell, $sourceSheet->getCell($sourceCell)->getValue());
                $sheet->getStyle($targetCell)->applyFromArray(
                    $sourceSheet->getStyle($sourceCell)->exportArray()
                );
            }
            $targetRow++;
        }

        foreach (range('A', $maxColumn) as $column) {
            $width = $sourceSheet->getColumnDimension($column)->getWidth();
            if ($width > 0) {
                $sheet->getColumnDimension($column)->setWidth($width);
            }
        }

        foreach ($sourceSheet->getMergeCells() as $mergeRange) {
            if (!preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/', $mergeRange, $matches)) {
                continue;
            }

            $rowStart = (int) $matches[2];
            $rowEnd = (int) $matches[4];

            if ($rowEnd < $startRow || $rowStart > $endRow) {
                continue;
            }

            $newRowStart = max($startRow, $rowStart) - $startRow + 1;
            $newRowEnd = min($endRow, $rowEnd) - $startRow + 1;
            $sheet->mergeCells($matches[1] . $newRowStart . ':' . $matches[3] . $newRowEnd);
        }

        $outputDirectory = dirname($outputPath);
        if (!is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0755, true);
        }

        (new Xlsx($spreadsheet))->save($outputPath);

        $this->info("Bulk upload template created: {$outputPath}");

        return self::SUCCESS;
    }
}
