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

        $sectors = Sector::with(['commitments' => function ($query) use ($startMonth, $endMonth, $year) {
            $query->withCount(['deliverables' => function ($q) use ($startMonth, $endMonth, $year) {
                $q->whereNotNull('end_date')
                    ->where('status', 'completed')
                    ->whereYear('end_date', $year)
                    ->whereMonth('end_date', '>=', $startMonth)
                    ->whereMonth('end_date', '<=', $endMonth);
            }]);
        }])->get();

        $snapshotData = [];
        foreach ($sectors->sortBy('id') as $sector) {
            $totalCommitments = $sector->commitments->count();
            $totalOutputs = $sector->commitments->sum('deliverables_count');
            $outputsDelivered = $sector->commitments->sum(function ($commitment) {
                return $commitment->deliverables_count;
            });

            $performancePercentage = ($totalOutputs > 0)
                ? ($totalCommitments > 0 ? ($outputsDelivered / $totalOutputs) * 100 : 0)
                : 0;
            $rating = $this->calculatePerformanceRating($performancePercentage);

            $snapshotData[] = [
                's_n' => $sector->id,
                'sector_name' => $sector->sector_name,
                'no_of_commitments' => $totalCommitments,
                'no_of_outputs' => $totalOutputs,
                'outputs_delivered' => $outputsDelivered,
                'rating' => $rating,
            ];
        }

        $summaryData = [];
        foreach ($sectors as $sector) {
            $commitments = $sector->commitments;
            $ministryCommitments = [];
            $totalDeliverables = 0;
            $completedDeliverables = 0;

            foreach ($commitments as $index => $commitment) {
                $totalDeliverables += $commitment->deliverables_count ?? 0;
                $completedDeliverables += $commitment->deliverables_count ?? 0;

                $performancePercentage = ($commitment->deliverables_count > 0)
                    ? ($commitment->deliverables_count / $commitment->deliverables_count) * 100
                    : 0;
                $performanceRating = $this->calculatePerformanceRating($performancePercentage);

                $ministryCommitments[] = [
                    's_n' => $index + 1,
                    'commitment' => $commitment->description ?? 'N/A',
                    'no_of_outputs' => $commitment->deliverables_count ?? 0,
                    'no_results_to_be_delivered' => $commitment->deliverables_count ?? 0,
                    'exceptional' => ($performancePercentage > 150) ? $commitment->deliverables_count : 0,
                    'above_expectation' => ($performancePercentage >= 50 && $performancePercentage <= 150) ? $commitment->deliverables_count : 0,
                    'meets_expectation' => ($performancePercentage >= 30 && $performancePercentage < 50) ? $commitment->deliverables_count : 0,
                    'needs_improvement' => ($performancePercentage >= 20 && $performancePercentage < 30) ? $commitment->deliverables_count : 0,
                    'below_minimum' => ($performancePercentage < 20) ? $commitment->deliverables_count : 0,
                    'overall_performance' => $performancePercentage . '%',
                    'rating' => $performanceRating,
                    'remarks' => '',
                    'check' => '',
                ];
            }

            $overallPerformance = ($totalDeliverables > 0)
                ? ($completedDeliverables / $totalDeliverables) * 100
                : 0;
            $overallRating = $this->calculatePerformanceRating($overallPerformance);

            $summaryData[$sector->sector_name] = [
                'commitments' => $ministryCommitments,
                'summary' => [
                    's_n' => '',
                    'commitment' => 'Total',
                    'no_of_outputs' => $totalDeliverables,
                    'no_results_to_be_delivered' => $completedDeliverables,
                    'exceptional' => array_sum(array_column($ministryCommitments, 'exceptional')),
                    'above_expectation' => array_sum(array_column($ministryCommitments, 'above_expectation')),
                    'meets_expectation' => array_sum(array_column($ministryCommitments, 'meets_expectation')),
                    'needs_improvement' => array_sum(array_column($ministryCommitments, 'needs_improvement')),
                    'below_minimum' => array_sum(array_column($ministryCommitments, 'below_minimum')),
                    'overall_performance' => $overallPerformance . '%',
                    'rating' => $overallRating,
                    'remarks' => '',
                    'check' => '',
                ],
            ];
        }

        $startMonthName = date('F', mktime(0, 0, 0, $startMonth, 1));
        $endMonthName = date('F', mktime(0, 0, 0, $endMonth, 1));
        $title = "$startMonthName to $endMonthName $year Snapshot View of MDA/Sector Performance";
        $summaryTitle = "$startMonthName to $endMonthName $year MDA/Sector Summary of Performance on Commitments";

        return view('pages.reports.index', compact('request','snapshotData', 'summaryData', 'title', 'summaryTitle', 'startMonth', 'endMonth', 'year'));
    }

    public function download(Request $request)
    {
        // Validate input for download
        $request->validate([
            'start_month' => 'required|integer|between:1,12',
            'end_month' => 'required|integer|between:1,12|gte:start_month',
            'year' => 'required|integer|digits:4',
        ]);

        $startMonth = $request->input('start_month');
        $endMonth = $request->input('end_month');
        $year = $request->input('year');

        $sectors = Sector::with(['commitments' => function ($query) use ($startMonth, $endMonth, $year) {
            $query->withCount(['deliverables' => function ($q) use ($startMonth, $endMonth, $year) {
                $q->whereNotNull('end_date')
                    ->where('status', 'completed')
                    ->whereYear('end_date', $year)
                    ->whereMonth('end_date', '>=', $startMonth)
                    ->whereMonth('end_date', '<=', $endMonth);
            }]);
        }])->get();

        $spreadsheet = new Spreadsheet();

        // Sheet 1: Overall Grand Summary
        $sheet1 = $spreadsheet->createSheet(0);
        $sheet1->setTitle('Overall Grand Summary');
        $startMonthName = date('F', mktime(0, 0, 0, $startMonth, 1));
        $endMonthName = date('F', mktime(0, 0, 0, $endMonth, 1));
        $title = "$startMonthName to $endMonthName $year Snapshot View of MDA/Sector Performance";
        $sheet1->setCellValue('A1', $title);
        $sheet1->mergeCells('A1:H1');
        $sheet1->getStyle('A1')->getFont()->setBold(true);
        $sheet1->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet1->setCellValue('A2', 'S/N');
        $sheet1->mergeCells('A2:A4');
        $sheet1->setCellValue('B2', 'Names of MDAs / Sector');
        $sheet1->mergeCells('B2:B4');
        $sheet1->setCellValue('C2', 'No. of Commitments');
        $sheet1->mergeCells('C2:C4');
        $sheet1->mergeCells('D2:E2');
        $sheet1->setCellValue('D2', 'No. of Outputs');
        $sheet1->getStyle('D2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->mergeCells('F2:G2');
        $sheet1->setCellValue('F2', 'Overall Performance');
        $sheet1->getStyle('F2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->setCellValue('H2', 'Check');

        $sheet1->getStyle('A2:H2')->getFont()->setBold(true);
        $sheet1->getStyle('A2:H2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet1->getColumnDimension('A')->setWidth(5);
        $sheet1->getColumnDimension('B')->setWidth(30);
        $sheet1->getColumnDimension('C')->setWidth(15);
        $sheet1->getColumnDimension('D')->setWidth(15);
        $sheet1->getColumnDimension('E')->setWidth(15);
        $sheet1->getColumnDimension('F')->setWidth(20);
        $sheet1->getColumnDimension('G')->setWidth(20);
        $sheet1->getColumnDimension('H')->setWidth(10);

        $row = 3;
        foreach ($sectors->sortBy('id') as $sector) {
            $totalCommitments = $sector->commitments->count();
            $totalOutputs = $sector->commitments->sum('deliverables_count');
            $outputsDelivered = $sector->commitments->sum(function ($commitment) {
                return $commitment->deliverables_count;
            });

            $performancePercentage = ($totalOutputs > 0)
                ? ($totalCommitments > 0 ? ($outputsDelivered / $totalOutputs) * 100 : 0)
                : 0;
            $rating = $this->calculatePerformanceRating($performancePercentage);

            $sheet1->setCellValue('A' . $row, $sector->id);
            $sheet1->setCellValue('B' . $row, $sector->sector_name);
            $sheet1->setCellValue('C' . $row, $totalCommitments);
            $sheet1->setCellValue('D' . $row, $totalOutputs);
            $sheet1->setCellValue('E' . $row, $outputsDelivered);
            $sheet1->setCellValue('F' . $row, '');
            $sheet1->setCellValue('G' . $row, $rating);
            $sheet1->setCellValue('H' . $row, '');

            $row++;
        }
        $sheet1->getStyle('A3:H' . ($row - 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Sheet 2: MDA_Sector Summary
        $sheet2 = $spreadsheet->createSheet(1);
        $sheet2->setTitle('MDA_Sector Summary');
        $title2 = "$startMonthName to $endMonthName $year MDA/Sector Summary of Performance on Commitments";
        $sheet2->setCellValue('A1', $title2);
        $sheet2->mergeCells('A1:L1');
        $sheet2->getStyle('A1')->getFont()->setBold(true);
        $sheet2->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet2->setCellValue('A2', 'S/N');
        $sheet2->setCellValue('B2', 'Commitments');
        $sheet2->setCellValue('C2', 'No. of Outputs');
        $sheet2->setCellValue('D2', 'No Results to be Delivered');
        $sheet2->mergeCells('E2:I2');
        $sheet2->setCellValue('E2', 'Performance for Each Result');
        $sheet2->getStyle('E2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->setCellValue('J2', 'Overall Performance');
        $sheet2->mergeCells('J2:K2');
        $sheet2->getStyle('J2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->setCellValue('L2', 'Check');

        $sheet2->setCellValue('E3', 'Exceptional');
        $sheet2->setCellValue('F3', 'Above Expectation');
        $sheet2->setCellValue('G3', 'Meets Expectation');
        $sheet2->setCellValue('H3', 'Needs Improvement');
        $sheet2->setCellValue('I3', 'Below Minimum');
        $sheet2->setCellValue('J3', 'Performance');
        $sheet2->setCellValue('K3', 'Rating');
        $sheet2->setCellValue('L3', '');

        $sheet2->setCellValue('E4', 'Above 50%');
        $sheet2->setCellValue('F4', '35% - 50%');
        $sheet2->setCellValue('G4', '30% - 34%');
        $sheet2->setCellValue('H4', '20% - 29%');
        $sheet2->setCellValue('I4', 'Below 20%');

        $sheet2->getStyle('A2:L4')->getFont()->setBold(true);
        $sheet2->getStyle('A2:L4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet2->getColumnDimension('A')->setWidth(5);
        $sheet2->getColumnDimension('B')->setWidth(40);
        $sheet2->getColumnDimension('C')->setWidth(15);
        $sheet2->getColumnDimension('D')->setWidth(15);
        $sheet2->getColumnDimension('E')->setWidth(15);
        $sheet2->getColumnDimension('F')->setWidth(15);
        $sheet2->getColumnDimension('G')->setWidth(15);
        $sheet2->getColumnDimension('H')->setWidth(15);
        $sheet2->getColumnDimension('I')->setWidth(15);
        $sheet2->getColumnDimension('J')->setWidth(15);
        $sheet2->getColumnDimension('K')->setWidth(15);
        $sheet2->getColumnDimension('L')->setWidth(10);

        $row = 5;
        foreach ($sectors as $sector) {
            $commitments = $sector->commitments;
            $sheet2->setCellValue('B' . $row, $sector->sector_name);
            $sheet2->mergeCells('B' . $row . ':L' . $row);
            $row++;

            $totalDeliverables = 0;
            $completedDeliverables = 0;
            foreach ($commitments as $index => $commitment) {
                $totalDeliverables += $commitment->deliverables_count ?? 0;
                $completedDeliverables += $commitment->deliverables_count ?? 0;

                $performancePercentage = ($commitment->deliverables_count > 0)
                    ? ($commitment->deliverables_count / $commitment->deliverables_count) * 100
                    : 0;
                $performanceRating = $this->calculatePerformanceRating($performancePercentage);

                $sheet2->setCellValue('A' . $row, $index + 1);
                $sheet2->setCellValue('B' . $row, $commitment->description ?? 'N/A');
                $sheet2->setCellValue('C' . $row, $commitment->deliverables_count ?? 0);
                $sheet2->setCellValue('D' . $row, $commitment->deliverables_count ?? 0);
                $sheet2->setCellValue('E' . $row, ($performancePercentage > 50) ? $commitment->deliverables_count : 0);
                $sheet2->setCellValue('F' . $row, ($performancePercentage >= 35 && $performancePercentage <= 50) ? $commitment->deliverables_count : 0);
                $sheet2->setCellValue('G' . $row, ($performancePercentage >= 30 && $performancePercentage < 35) ? $commitment->deliverables_count : 0);
                $sheet2->setCellValue('H' . $row, ($performancePercentage >= 20 && $performancePercentage < 30) ? $commitment->deliverables_count : 0);
                $sheet2->setCellValue('I' . $row, ($performancePercentage < 20) ? $commitment->deliverables_count : 0);
                $sheet2->setCellValue('J' . $row, $performancePercentage . '%');
                $sheet2->setCellValue('K' . $row, $performanceRating);
                $sheet2->setCellValue('L' . $row, '');

                $row++;
            }

            $overallPerformance = ($totalDeliverables > 0)
                ? ($completedDeliverables / $totalDeliverables) * 100
                : 0;
            $overallRating = $this->calculatePerformanceRating($overallPerformance);

            $sheet2->setCellValue('A' . $row, '');
            $sheet2->setCellValue('B' . $row, 'Total');
            $sheet2->setCellValue('C' . $row, $totalDeliverables);
            $sheet2->setCellValue('D' . $row, $completedDeliverables);
            $sheet2->setCellValue('E' . $row, array_sum(array_column($commitments->map(function ($c) {
                $perf = ($c->deliverables_count > 0) ? ($c->deliverables_count / $c->deliverables_count) * 100 : 0;
                return ($perf > 50) ? $c->deliverables_count : 0;
            })->all(), 0)));
            $sheet2->setCellValue('F' . $row, array_sum(array_column($commitments->map(function ($c) {
                $perf = ($c->deliverables_count > 0) ? ($c->deliverables_count / $c->deliverables_count) * 100 : 0;
                return ($perf >= 35 && $perf <= 50) ? $c->deliverables_count : 0;
            })->all(), 0)));
            $sheet2->setCellValue('G' . $row, array_sum(array_column($commitments->map(function ($c) {
                $perf = ($c->deliverables_count > 0) ? ($c->deliverables_count / $c->deliverables_count) * 100 : 0;
                return ($perf >= 30 && $perf < 35) ? $c->deliverables_count : 0;
            })->all(), 0)));
            $sheet2->setCellValue('H' . $row, array_sum(array_column($commitments->map(function ($c) {
                $perf = ($c->deliverables_count > 0) ? ($c->deliverables_count / $c->deliverables_count) * 100 : 0;
                return ($perf >= 20 && $perf < 30) ? $c->deliverables_count : 0;
            })->all(), 0)));
            $sheet2->setCellValue('I' . $row, array_sum(array_column($commitments->map(function ($c) {
                $perf = ($c->deliverables_count > 0) ? ($c->deliverables_count / $c->deliverables_count) * 100 : 0;
                return ($perf < 20) ? $c->deliverables_count : 0;
            })->all(), 0)));
            $sheet2->setCellValue('J' . $row, $overallPerformance . '%');
            $sheet2->setCellValue('K' . $row, $overallRating);
            $sheet2->setCellValue('L' . $row, '');
            $sheet2->getStyle('A' . $row . ':L' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('DCE6F1');
            $row++;
        }

        $sheet2->getStyle('A5:L' . ($row - 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'sector_performance_report_' . $year . '_' . $startMonth . '-' . $endMonth . '_' . time() . '.xlsx';
        $writer->save(storage_path('app/public/' . $fileName));

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
