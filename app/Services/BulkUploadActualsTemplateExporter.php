<?php

namespace App\Services;

use App\Models\Commitment;
use App\Models\Framework;
use App\Models\KpiTarget;
use App\Models\PerformanceTracking;
use App\Models\Sector;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BulkUploadActualsTemplateExporter
{
    public function download(Sector $sector, Framework $framework): BinaryFileResponse
    {
        $path = $this->build($sector, $framework);

        return response()->download(
            $path,
            sprintf('bulk-actuals-upload-%s-%s.xlsx', $sector->id, $framework->year),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    public function build(Sector $sector, Framework $framework): string
    {
        $baseTemplate = resource_path('templates/bulk-performance-upload-template.xlsx');
        $sheet = is_file($baseTemplate)
            ? IOFactory::load($baseTemplate)->getActiveSheet()
            : null;

        $spreadsheet = new Spreadsheet();
        $target = $spreadsheet->getActiveSheet();
        $target->setTitle('Actuals');

        if ($sheet) {
            for ($row = 1; $row <= 5; $row++) {
                foreach (range('A', 'Q') as $column) {
                    $target->setCellValue($column . $row, $sheet->getCell($column . $row)->getValue());
                }
            }
        }

        $target->setCellValue('A1', strtoupper($sector->sector_name));
        $target->setCellValue('A2', 'ANNUAL DELIVERY PERFORMANCE MANAGEMENT FRAMEWORK');
        $target->setCellValue('A3', $framework->year . ' (JANUARY - DECEMBER, ' . $framework->year . ') PERFORMANCE ASSESSMENT');

        $rowIndex = 6;
        $commitmentNumber = 0;

        $commitments = Commitment::query()
            ->where('sector_id', $sector->id)
            ->where('framework_id', $framework->id)
            ->orderBy('name')
            ->with(['deliverables' => function ($query) use ($framework) {
                $query->where('framework_id', $framework->id)
                    ->orderBy('deliverable')
                    ->with(['kpis' => function ($kpiQuery) use ($framework) {
                        $kpiQuery->where('framework_id', $framework->id)
                            ->where('year', $framework->year)
                            ->orderBy('kpi');
                    }]);
            }])
            ->get();

        $resultNo = 0;
        $currentDeliverable = null;
        $deliverableNumber = 0;

        foreach ($commitments as $commitment) {
            $commitmentNumber++;
            $commitmentTitle = preg_match('/^commitment\s+\d+/i', $commitment->name)
                ? $commitment->name
                : 'Commitment ' . $commitmentNumber . ': ' . $commitment->name;

            $target->setCellValue('A' . $rowIndex, $commitmentTitle);
            $rowIndex++;

            foreach ($commitment->deliverables as $deliverable) {
                foreach ($deliverable->kpis as $kpi) {
                    $resultNo++;
                    if ($currentDeliverable !== $deliverable->deliverable) {
                        $deliverableNumber++;
                        $currentDeliverable = $deliverable->deliverable;
                    }

                    $annualTarget = KpiTarget::query()
                        ->where('kpi_id', $kpi->id)
                        ->where('year', $framework->year)
                        ->value('target');

                    $trackings = PerformanceTracking::query()
                        ->where('kpi_id', $kpi->id)
                        ->where('year', $framework->year)
                        ->get()
                        ->keyBy('quarter');

                    $target->setCellValue('A' . $rowIndex, $deliverableNumber);
                    $target->setCellValue('B' . $rowIndex, $deliverable->deliverable);
                    $target->setCellValue('C' . $rowIndex, $kpi->kpi);
                    $target->setCellValue('D' . $rowIndex, $resultNo);
                    $target->setCellValue('E' . $rowIndex, $kpi->target_value);
                    $target->setCellValue('F' . $rowIndex, $annualTarget);

                    foreach ([1 => 'G', 2 => 'I', 3 => 'K', 4 => 'M'] as $quarter => $targetColumn) {
                        $tracking = $trackings->get($quarter);
                        $target->setCellValue($targetColumn . $rowIndex, $tracking?->milestone);
                    }

                    foreach ([1 => 'H', 2 => 'J', 3 => 'L', 4 => 'N'] as $quarter => $actualColumn) {
                        $tracking = $trackings->get($quarter);
                        $target->setCellValue($actualColumn . $rowIndex, $tracking?->actual_value);
                    }

                    $fullYearActual = $trackings->sum(fn ($tracking) => (float) ($tracking->actual_value ?? 0));
                    $target->setCellValue('O' . $rowIndex, $annualTarget);
                    $target->setCellValue('P' . $rowIndex, $fullYearActual > 0 ? $fullYearActual : null);

                    $latestRemarks = $trackings
                        ->sortByDesc('updated_at')
                        ->first(fn ($tracking) => !empty($tracking->remarks));

                    $target->setCellValue('Q' . $rowIndex, $latestRemarks?->remarks);

                    $rowIndex++;
                }

                $currentDeliverable = null;
            }
        }

        $tempPath = storage_path('app/temp/bulk-actuals-' . uniqid('', true) . '.xlsx');
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        (new Xlsx($spreadsheet))->save($tempPath);

        return $tempPath;
    }
}
