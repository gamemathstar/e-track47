<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth");
    }

    public function index()
    {
        return view('pages.reports.index');
    }

    public function generate(Request $request)
    {
        // Validate input
        $request->validate([
            'start_month' => 'required|integer|between:1,12',
            'end_month' => 'required|integer|between:1,12|gte:start_month',
            'year' => 'required|integer|digits:4',
        ]);

        $startMonth = $request->input('start_month');
        $endMonth = $request->input('end_month');
        $year = $request->input('year');

        // Fetch sectors with commitments and deliverables, filtered by date range
        $sectors = Sector::with(['commitments' => function ($query) use ($startMonth, $endMonth, $year) {
            $query->withCount(['deliverables' => function ($q) use ($startMonth, $endMonth, $year) {
                $q->whereNotNull('end_date')
                    ->where('status', 'completed')
                    ->whereYear('end_date', $year)
                    ->whereMonth('end_date', '>=', $startMonth)
                    ->whereMonth('end_date', '<=', $endMonth);
            }]);
        }])->get();

        $reportData = [];
        foreach ($sectors->sortBy('id') as $sector) {
            $totalCommitments = $sector->commitments->count();
            $totalOutputs = $sector->commitments->sum('deliverables_count'); // Assuming deliverables_count is populated
            $outputsDelivered = $sector->commitments->sum(function ($commitment) {
                return $commitment->deliverables_count; // Adjust based on your data
            });

            $performancePercentage = ($totalOutputs > 0)
                ? ($totalCommitments > 0 ? ($outputsDelivered / $totalOutputs) * 100 : 0)
                : 0;
            $rating = $this->calculatePerformanceRating($performancePercentage);

            $reportData[] = [
                's_n' => $sector->id,
                'sector_name' => $sector->sector_name,
                'no_of_commitments' => $totalCommitments,
                'no_of_outputs' => $totalOutputs,
                'outputs_delivered' => $outputsDelivered,
                'rating' => $rating,
            ];
        }

        $startMonthName = date('F', mktime(0, 0, 0, $startMonth, 1));
        $endMonthName = date('F', mktime(0, 0, 0, $endMonth, 1));
        $title = "$startMonthName to $endMonthName $year Snapshot View of MDA/Sector Performance";

        return view('pages.reports.index', compact('request','reportData', 'title'));
    }

    public function download(Request $request)
    {
        // Validate input for download (same as generate)
        $request->validate([
            'start_month' => 'required|integer|between:1,12',
            'end_month' => 'required|integer|between:1,12|gte:start_month',
            'year' => 'required|integer|digits:4',
        ]);

        $startMonth = $request->input('start_month');
        $endMonth = $request->input('end_month');
        $year = $request->input('year');

        // Fetch sectors with commitments and deliverables, filtered by date range
        $sectors = Sector::with(['commitments' => function ($query) use ($startMonth, $endMonth, $year) {
            $query->withCount(['deliverables' => function ($q) use ($startMonth, $endMonth, $year) {
                $q->whereNotNull('end_date')
                    ->where('status', 'completed')
                    ->whereYear('end_date', $year)
                    ->whereMonth('end_date', '>=', $startMonth)
                    ->whereMonth('end_date', '<=', $endMonth);
            }]);
        }])->get();

        // Initialize PhpSpreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sector Performance Report');

        // Set main title with dynamic date range
        $startMonthName = date('F', mktime(0, 0, 0, $startMonth, 1));
        $endMonthName = date('F', mktime(0, 0, 0, $endMonth, 1));
        $title = "$startMonthName to $endMonthName $year Snapshot View of MDA/Sector Performance";
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Set subheaders
        $sheet->setCellValue('A2', 'S/N');
        $sheet->setCellValue('B2', 'Names of MDAs / Sector');
        $sheet->setCellValue('C2', 'No. of Commitments');
        $sheet->mergeCells('D2:E2');
        $sheet->setCellValue('D2', 'No. of Outputs');
        $sheet->getStyle('D2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('F2:G2');
        $sheet->setCellValue('F2', 'Overall Performance');
        $sheet->getStyle('F2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('H2', 'Check');

        // Set subheader styles
        $sheet->getStyle('A2:H2')->getFont()->setBold(true);
        $sheet->getStyle('A2:H2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Define column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(10);

        // Populate data
        $row = 3;
        foreach ($sectors->sortBy('id') as $sector) {
            $totalCommitments = $sector->commitments->count();
            $totalOutputs = $sector->commitments->sum('deliverables_count'); // Assuming deliverables_count is populated
            $outputsDelivered = $sector->commitments->sum(function ($commitment) {
                return $commitment->deliverables_count; // Adjust based on your data
            });

            $performancePercentage = ($totalOutputs > 0)
                ? ($totalCommitments > 0 ? ($outputsDelivered / $totalOutputs) * 100 : 0)
                : 0;
            $rating = $this->calculatePerformanceRating($performancePercentage);

            $sheet->setCellValue('A' . $row, $sector->id);
            $sheet->setCellValue('B' . $row, $sector->sector_name);
            $sheet->setCellValue('C' . $row, $totalCommitments);
            $sheet->setCellValue('D' . $row, $totalOutputs);
            $sheet->setCellValue('E' . $row, $outputsDelivered);
            $sheet->setCellValue('F' . $row, ''); // Placeholder for Performance
            $sheet->setCellValue('G' . $row, $rating);
            $sheet->setCellValue('H' . $row, ''); // Placeholder for Check

            $row++;
        }

        // Set data styles
        $sheet->getStyle('A3:H' . ($row - 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Create and save the Excel file
        $writer = new Xlsx($spreadsheet);
        $fileName = 'sector_performance_report_' . $year . '_' . $startMonth . '-' . $endMonth . '_' . time() . '.xlsx';
        $writer->save(storage_path('app/public/' . $fileName));

        // Return the file for download
        return response()->download(storage_path('app/public/' . $fileName))->deleteFileAfterSend(true);
    }

    private function calculatePerformanceRating($percentage): string
    {
        if ($percentage >= 50) {
            return $percentage > 150 ? 'Exceptional (Distinction)' : 'Above Expectation (Very Good)';
        } elseif ($percentage >= 30 && $percentage < 50) {
            return 'Meets Expectation';
        } elseif ($percentage >= 20 && $percentage < 30) {
            return 'Needs Improvement (Fair)';
        } else {
            return 'Below Minimum Expectation';
        }
    }
}
