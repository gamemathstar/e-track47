<?php

namespace App\Services;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BulkUploadReportExporter
{
    public function downloadData(array $report): BinaryFileResponse
    {
        $path = $this->build($report);
        $reference = $report['reference'] ?? 'submission';

        return response()->download(
            $path,
            sprintf('bulk-upload-data-%s.xlsx', $reference),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    public function build(array $report): string
    {
        $spreadsheet = new Spreadsheet();
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Summary');

        $meta = $report['meta'] ?? [];
        $uploadMode = $report['upload_mode'] ?? 'structure';
        $submittedAt = $this->parseTimestamp($report['submitted_at'] ?? now());
        $stats = $report['import_stats'] ?? [];

        $summaryRows = [
            ['Bulk Upload Submission Report'],
            [],
            ['Reference', $report['reference'] ?? '—'],
            ['Sector', $meta['sector_name'] ?? '—'],
            ['Fiscal Year', $meta['framework_year'] ?? '—'],
            ['Submitted By', $report['submitted_by'] ?? '—'],
            ['Submitted At', $submittedAt->format('Y-m-d H:i:s')],
            ['Upload Mode', $uploadMode === 'actuals' ? 'Actuals' : 'Structure'],
            ['Source File', $meta['file_name'] ?? '—'],
        ];

        if ($uploadMode === 'actuals' && !empty($meta['reporting_quarter'])) {
            $summaryRows[] = ['Reporting Quarter', 'Q' . $meta['reporting_quarter']];
        }

        $summaryRows[] = [];
        $summaryRows[] = ['Import Summary'];

        if ($uploadMode === 'actuals') {
            $summaryRows = array_merge($summaryRows, [
                ['Rows Processed', $stats['rows_processed'] ?? 0],
                ['Actuals Updated', $stats['actuals_updated'] ?? 0],
                ['New Submissions', $stats['actuals_submitted'] ?? 0],
                ['Skipped (Locked)', $stats['skipped_locked'] ?? 0],
                ['Skipped (No KPI)', $stats['skipped_no_kpi'] ?? 0],
                ['Skipped (No Milestone)', $stats['skipped_no_milestone'] ?? 0],
            ]);
        } else {
            $summaryRows = array_merge($summaryRows, [
                ['Commitments Created', $stats['commitments_created'] ?? 0],
                ['Commitments Matched', $stats['commitments_matched'] ?? 0],
                ['Deliverables Created', $stats['deliverables_created'] ?? 0],
                ['Deliverables Matched', $stats['deliverables_matched'] ?? 0],
                ['KPIs Created', $stats['kpis_created'] ?? 0],
                ['KPIs Matched', $stats['kpis_matched'] ?? 0],
                ['Milestones Created', $stats['milestones_created'] ?? 0],
                ['Milestones Updated', $stats['milestones_updated'] ?? 0],
            ]);
        }

        $summaryRows[] = [];
        $summaryRows[] = ['Quarterly Performance'];

        foreach ($report['quarterly_averages'] ?? [] as $quarter) {
            $summaryRows[] = [$quarter['label'] ?? 'Quarter', ($quarter['percent'] ?? 0) . '%'];
        }

        $rowIndex = 1;
        foreach ($summaryRows as $row) {
            $summarySheet->fromArray($row, null, 'A' . $rowIndex);
            $rowIndex++;
        }

        $dataSheet = $spreadsheet->createSheet();
        $dataSheet->setTitle('Records');

        $headers = [
            'S/N',
            'Commitment',
            'Deliverable',
            'KPI',
            'Annual Target',
            'Q1 Target',
            'Q1 Actual',
            'Q2 Target',
            'Q2 Actual',
            'Q3 Target',
            'Q3 Actual',
            'Q4 Target',
            'Q4 Actual',
            'Full Year Target',
            'Full Year Actual',
            'Remarks',
            'Status',
        ];

        $dataSheet->fromArray($headers, null, 'A1');

        $records = $report['rows'] ?? $report['kpis'] ?? [];
        $dataRow = 2;

        foreach ($records as $record) {
            $dataSheet->fromArray([
                $record['sn'] ?? '',
                $record['commitment'] ?? '',
                $record['deliverable'] ?? '',
                $record['kpi'] ?? '',
                $record['target'] ?? $record['full_year_target'] ?? '',
                $record['q1_target'] ?? '',
                $record['q1_actual'] ?? '',
                $record['q2_target'] ?? '',
                $record['q2_actual'] ?? '',
                $record['q3_target'] ?? '',
                $record['q3_actual'] ?? '',
                $record['q4_target'] ?? '',
                $record['q4_actual'] ?? '',
                $record['full_year_target'] ?? '',
                $record['full_year_actual'] ?? '',
                $record['remarks'] ?? '',
                $record['status'] ?? '',
            ], null, 'A' . $dataRow);

            $dataRow++;
        }

        foreach (range('A', 'Q') as $column) {
            $dataSheet->getColumnDimension($column)->setAutoSize(true);
        }

        $tempPath = storage_path('app/temp/bulk-upload-report-' . uniqid('', true) . '.xlsx');
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        (new Xlsx($spreadsheet))->save($tempPath);

        return $tempPath;
    }

    private function parseTimestamp(mixed $value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        return Carbon::parse($value);
    }
}
