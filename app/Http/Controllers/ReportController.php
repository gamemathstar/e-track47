<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
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

        // Create date range for filtering
        $startDate = Carbon::createFromDate($year, $startMonth, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $endMonth, 1)->endOfMonth();

        // Get sectors with commitments that have deliverables within the specified date range
        $sectors = Sector::with(['commitments' => function ($query) use ($startDate, $endDate, $year) {
            $query->withCount(['deliverables' => function ($q) use ($startDate, $endDate, $year) {
                $q->whereNotNull('end_date')
                    ->where('status', 'completed')
                    ->whereYear('end_date', $year)
                    ->whereBetween('end_date', [$startDate, $endDate]);
            }])
                ->withCount(['deliverables as total_deliverables' => function ($q) use ($startDate, $endDate, $year) {
                    $q->whereNotNull('end_date')
                        ->whereYear('end_date', $year)
                        ->whereBetween('end_date', [$startDate, $endDate]);
                }]);
        }])->get();

        $snapshotData = [];
        foreach ($sectors->sortBy('id') as $sector) {
            // Filter commitments that have deliverables in the specified date range
            $filteredCommitments = $sector->commitments->filter(function ($commitment) use ($startDate, $endDate, $year) {
                return $commitment->deliverables()
                    ->whereNotNull('end_date')
                    ->whereYear('end_date', $year)
                    ->whereBetween('end_date', [$startDate, $endDate])
                    ->exists();
            });

            $totalCommitments = $filteredCommitments->count();
            $totalOutputs = $filteredCommitments->sum('total_deliverables');
            $outputsDelivered = $filteredCommitments->sum('deliverables_count');

            // Calculate total KPIs for all deliverables in the date range
            $totalKpis = 0;
            foreach ($filteredCommitments as $commitment) {
                $kpiCount = DB::table('deliverables as d')
                    ->join('kpis as k', 'd.id', '=', 'k.deliverable_id')
                    ->where('d.commitment_id', $commitment->id)
                    ->whereNotNull('d.end_date')
                    ->whereYear('d.end_date', $year)
                    ->whereBetween('d.end_date', [$startDate, $endDate])
                    ->count();
                $totalKpis += $kpiCount;
            }

            // Calculate KPI performance tracking ratio for the sector
            $totalKpiRatios = 0;
            $validKpiCount = 0;

            foreach ($filteredCommitments as $commitment) {
                $kpiPerformanceData = DB::table('deliverables as d')
                    ->join('kpis as k', 'd.id', '=', 'k.deliverable_id')
                    ->join('performance_trackings as pt', 'k.id', '=', 'pt.kpi_id')
                    ->where('d.commitment_id', $commitment->id)
                    ->whereNotNull('d.end_date')
                    ->whereYear('d.end_date', $year)
                    ->whereBetween('d.end_date', [$startDate, $endDate])
                    ->whereNotNull('pt.actual_value')
                    ->whereNotNull('pt.milestone')
                    ->where('pt.milestone', '>', 0)
                    ->whereRaw('pt.actual_value REGEXP "^[0-9]+\.?[0-9]*$"')
                    ->whereRaw('pt.milestone REGEXP "^[0-9]+\.?[0-9]*$"')
                    ->select('pt.actual_value', 'pt.milestone as target')
                    ->get();

                foreach ($kpiPerformanceData as $kpiData) {
                    $ratio = $kpiData->actual_value / $kpiData->target;
                    $totalKpiRatios += $ratio;
                    $validKpiCount++;
                }
            }

            // Calculate average performance ratio
            $averagePerformanceRatio = ($validKpiCount > 0) ? ($totalKpiRatios / $validKpiCount) * 100 : 0;

            $performancePercentage = ($totalOutputs > 0)
                ? ($outputsDelivered / $totalOutputs) * 100
                : 0;
            $rating = $this->calculatePerformanceRating(round($averagePerformanceRatio, 2));

            $snapshotData[] = [
                's_n' => $sector->id,
                'sector_name' => $sector->sector_name,
                'no_of_commitments' => $totalCommitments,
                'no_of_outputs' => $totalOutputs,
                'no_of_kpis' => $totalKpis,
                'outputs_delivered' => $outputsDelivered,
                'performance' => round($averagePerformanceRatio, 2),
                'rating' => $rating,
            ];
        }

        $summaryData = [];
        foreach ($sectors as $sector) {
            // Filter commitments that have deliverables in the specified date range
            $filteredCommitments = $sector->commitments->filter(function ($commitment) use ($startDate, $endDate, $year) {
                return $commitment->deliverables()
                    ->whereNotNull('end_date')
                    ->whereYear('end_date', $year)
                    ->whereBetween('end_date', [$startDate, $endDate])
                    ->exists();
            });

            $ministryCommitments = [];
            $totalDeliverables = 0;
            $completedDeliverables = 0;

            foreach ($filteredCommitments as $index => $commitment) {
                $totalDeliverables += $commitment->total_deliverables ?? 0;
                $completedDeliverables += $commitment->deliverables_count ?? 0;

                // Calculate KPIs for this commitment
                $commitmentKpis = DB::table('deliverables as d')
                    ->join('kpis as k', 'd.id', '=', 'k.deliverable_id')
                    ->where('d.commitment_id', $commitment->id)
                    ->whereNotNull('d.end_date')
                    ->whereYear('d.end_date', $year)
                    ->whereBetween('d.end_date', [$startDate, $endDate])
                    ->count();

                // Calculate KPI performance tracking ratio for this commitment
                $commitmentKpiRatios = 0;
                $commitmentValidKpiCount = 0;

                $commitmentKpiPerformanceData = DB::table('deliverables as d')
                    ->join('kpis as k', 'd.id', '=', 'k.deliverable_id')
                    ->join('performance_trackings as pt', 'k.id', '=', 'pt.kpi_id')
                    ->where('d.commitment_id', $commitment->id)
                    ->whereNotNull('d.end_date')
                    ->whereYear('d.end_date', $year)
                    ->whereBetween('d.end_date', [$startDate, $endDate])
                    ->whereNotNull('pt.actual_value')
                    ->whereNotNull('pt.milestone')
                    ->where('pt.milestone', '>', 0)
                    ->whereRaw('pt.actual_value REGEXP "^[0-9]+\.?[0-9]*$"')
                    ->whereRaw('pt.milestone REGEXP "^[0-9]+\.?[0-9]*$"')
                    ->select('pt.actual_value', 'pt.milestone as target')
                    ->get();

                foreach ($commitmentKpiPerformanceData as $kpiData) {
                    $ratio = $kpiData->actual_value / $kpiData->target;
                    $commitmentKpiRatios += $ratio;
                    $commitmentValidKpiCount++;
                }

                // Calculate average performance ratio for this commitment
                $commitmentAveragePerformanceRatio = ($commitmentValidKpiCount > 0) ? ($commitmentKpiRatios / $commitmentValidKpiCount) * 100 : 0;

                $performancePercentage = ($commitment->total_deliverables > 0)
                    ? ($commitment->deliverables_count / $commitment->total_deliverables) * 100
                    : 0;
                $performanceRating = $this->calculatePerformanceRating($performancePercentage);

                $ministryCommitments[] = [
                    's_n' => $index + 1,
                    'commitment' => $commitment->description ?? 'N/A',
                    'no_of_outputs' => $commitment->total_deliverables ?? 0,
                    'no_of_kpis' => $commitmentKpis,
                    'no_results_to_be_delivered' => $commitment->deliverables_count ?? 0,
                    'exceptional' => ($performancePercentage > 150) ? $commitment->deliverables_count : 0,
                    'above_expectation' => ($performancePercentage >= 50 && $performancePercentage <= 150) ? $commitment->deliverables_count : 0,
                    'meets_expectation' => ($performancePercentage >= 30 && $performancePercentage < 50) ? $commitment->deliverables_count : 0,
                    'needs_improvement' => ($performancePercentage >= 20 && $performancePercentage < 30) ? $commitment->deliverables_count : 0,
                    'below_minimum' => ($performancePercentage < 20) ? $commitment->deliverables_count : 0,
                    'overall_performance' => round($commitmentAveragePerformanceRatio, 2) . '%',
                    'rating' => $performanceRating,
                    'remarks' => '',
                    'check' => '',
                ];
            }

            // Calculate total KPIs for the sector
            $totalSectorKpis = array_sum(array_column($ministryCommitments, 'no_of_kpis'));

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
                    'no_of_kpis' => $totalSectorKpis,
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

        // Check if this is an AJAX request
        if ($request->ajax()) {
            // Generate HTML content for AJAX response
            $html = view('pages.reports.partials.report-content', compact(
                'snapshotData',
                'summaryData',
                'title',
                'summaryTitle',
                'startMonth',
                'endMonth',
                'year'
            ))->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'title' => $title,
                'summaryTitle' => $summaryTitle
            ]);
        }

        return view('pages.reports.index', compact('request', 'snapshotData', 'summaryData', 'title', 'summaryTitle', 'startMonth', 'endMonth', 'year'));
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

        // Create date range for filtering
        $startDate = Carbon::createFromDate($year, $startMonth, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $endMonth, 1)->endOfMonth();

        $sectors = Sector::with(['commitments' => function ($query) use ($startDate, $endDate, $year) {
            $query->withCount(['deliverables' => function ($q) use ($startDate, $endDate, $year) {
                $q->whereNotNull('end_date')
                    ->where('status', 'completed')
                    ->whereYear('end_date', $year)
                    ->whereBetween('end_date', [$startDate, $endDate]);
            }])
                ->withCount(['deliverables as total_deliverables' => function ($q) use ($startDate, $endDate, $year) {
                    $q->whereNotNull('end_date')
                        ->whereYear('end_date', $year)
                        ->whereBetween('end_date', [$startDate, $endDate]);
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
            // Filter commitments that have deliverables in the specified date range
            $filteredCommitments = $sector->commitments->filter(function ($commitment) use ($startDate, $endDate, $year) {
                return $commitment->deliverables()
                    ->whereNotNull('end_date')
                    ->whereYear('end_date', $year)
                    ->whereBetween('end_date', [$startDate, $endDate])
                    ->exists();
            });

            $totalCommitments = $filteredCommitments->count();
            $totalOutputs = $filteredCommitments->sum('total_deliverables');
            $outputsDelivered = $filteredCommitments->sum('deliverables_count');

            // Calculate total KPIs for all deliverables in the date range
            $totalKpis = 0;
            foreach ($filteredCommitments as $commitment) {
                $kpiCount = DB::table('deliverables as d')
                    ->join('kpis as k', 'd.id', '=', 'k.deliverable_id')
                    ->where('d.commitment_id', $commitment->id)
                    ->whereNotNull('d.end_date')
                    ->whereYear('d.end_date', $year)
                    ->whereBetween('d.end_date', [$startDate, $endDate])
                    ->count();
                $totalKpis += $kpiCount;
            }

            // Calculate KPI performance tracking ratio for the sector
            $totalKpiRatios = 0;
            $validKpiCount = 0;

            foreach ($filteredCommitments as $commitment) {
                $kpiPerformanceData = DB::table('deliverables as d')
                    ->join('kpis as k', 'd.id', '=', 'k.deliverable_id')
                    ->join('performance_trackings as pt', 'k.id', '=', 'pt.kpi_id')
                    ->where('d.commitment_id', $commitment->id)
                    ->whereNotNull('d.end_date')
                    ->whereYear('d.end_date', $year)
                    ->whereBetween('d.end_date', [$startDate, $endDate])
                    ->whereNotNull('pt.actual_value')
                    ->whereNotNull('pt.milestone')
                    ->where('pt.milestone', '>', 0)
                    ->whereRaw('pt.actual_value REGEXP "^[0-9]+\.?[0-9]*$"')
                    ->whereRaw('pt.milestone REGEXP "^[0-9]+\.?[0-9]*$"')
                    ->select('pt.actual_value', 'pt.milestone as target')
                    ->get();

                foreach ($kpiPerformanceData as $kpiData) {
                    $ratio = $kpiData->actual_value / $kpiData->target;
                    $totalKpiRatios += $ratio;
                    $validKpiCount++;
                }
            }

            // Calculate average performance ratio
            $averagePerformanceRatio = ($validKpiCount > 0) ? ($totalKpiRatios / $validKpiCount) * 100 : 0;

            $performancePercentage = ($totalOutputs > 0)
                ? ($outputsDelivered / $totalOutputs) * 100
                : 0;
            $rating = $this->calculatePerformanceRating($performancePercentage);

            $sheet1->setCellValue('A' . $row, $sector->id);
            $sheet1->setCellValue('B' . $row, $sector->sector_name);
            $sheet1->setCellValue('C' . $row, $totalCommitments);
            $sheet1->setCellValue('D' . $row, $totalOutputs);
            $sheet1->setCellValue('E' . $row, $totalKpis);
            $sheet1->setCellValue('F' . $row, $outputsDelivered);
            $sheet1->setCellValue('G' . $row, round($averagePerformanceRatio, 2));
            $sheet1->setCellValue('H' . $row, $rating);
            $sheet1->setCellValue('I' . $row, '');

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
            $sheet2->getStyle('A' . $row . ':L' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCE6F1');
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

    private function getPerformanceClass($performanceRatio)
    {
        if ($performanceRatio === 'NA') {
            return 'fair';
        }

        // Remove % sign and convert to number
        $ratio = (float)str_replace('%', '', $performanceRatio);

        if ($ratio >= 100) {
            return 'excellent';
        } elseif ($ratio >= 70) {
            return 'good';
        } elseif ($ratio >= 40) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    public function comprehensiveReport(Request $request)
    {
        $year = $request->input('year', date('Y'));

        try {
            // Get comprehensive KPI tracking data
            $reportData = $this->getComprehensiveReportData($year);
        } catch (Exception $e) {
            // Handle database errors gracefully
            $reportData = [];
            session()->flash('error', 'Database tables not found. Please run database migrations first.');
        }

        return view('pages.reports.comprehensive', compact('reportData', 'year'));
    }

    public function downloadComprehensiveReport(Request $request)
    {
        $year = $request->input('year', date('Y'));

        try {
            // Get comprehensive KPI tracking data
            $reportData = $this->getComprehensiveReportData($year);
        } catch (Exception $e) {
            // Handle database errors gracefully
            return redirect()->back()->with('error', 'Database tables not found. Please run database migrations first.');
        }

        // Create Excel file
        $spreadsheet = new Spreadsheet();

        // Create individual sector sheets and get commitment average row mapping
        $sectorData = $this->createIndividualSectorSheets($spreadsheet, $year);
        $commitmentAverageRows = $sectorData['commitmentAverageRows'];
        $sectorOverallAverageRows = $sectorData['sectorOverallAverageRows'];

        // Create Overall Summary sheet
        $this->createOverallSummarySheet($spreadsheet, $year, $sectorOverallAverageRows);

        // Create Grand Summary sheet
        $this->createGrandSummarySheet($spreadsheet, $year, $sectorOverallAverageRows);

        // Create Sector Summary Details sheet
        $this->createSectorSummaryDetailsSheet($spreadsheet, $year, $commitmentAverageRows);

        //  RE-ORDER THE SHEETS HERE
        // Reorder sheets: Overall Summary at index 0, Grand Summary at index 1, Sector Summary Details at index 2
        $overallSummarySheet = $spreadsheet->getSheetByName('Overall Summary');
        $grandSummarySheet = $spreadsheet->getSheetByName('Grand Summary-Sector_MDAs+');
        $sectorSummarySheet = $spreadsheet->getSheetByName('Sector_MDAs Summary Details');

        if ($overallSummarySheet) {
            $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($overallSummarySheet));
            $spreadsheet->addSheet($overallSummarySheet, 0);

            // Set this sheet as the active sheet
            $spreadsheet->setActiveSheetIndex($spreadsheet->getIndex($overallSummarySheet));
        }

        if ($grandSummarySheet) {
            $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($grandSummarySheet));
            $spreadsheet->addSheet($grandSummarySheet, 1);
        }

        if ($sectorSummarySheet) {
            $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($sectorSummarySheet));
            $spreadsheet->addSheet($sectorSummarySheet, 2);
        }

        // Create the Excel file
        $writer = new Xlsx($spreadsheet);
        $filename = "All_Sectors_MDAs_Full_Year_Assessment_Reporting_{$year}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    private function getComprehensiveReportData($year)
    {
        // Get main report data with the correct structure
        $reportData = DB::table('sectors as s')
            ->join('commitments as c', 'c.sector_id', '=', 's.id')
            ->join('deliverables as d', 'd.commitment_id', '=', 'c.id')
            ->join('kpis as k', 'k.deliverable_id', '=', 'd.id')
            ->leftJoin('kpi_targets as kt', function ($join) use ($year) {
                $join->on('kt.kpi_id', '=', 'k.id')
                    ->where('kt.year', '=', $year);
            })
            ->leftJoin('performance_trackings as pt', function ($join) use ($year) {
                $join->on('pt.kpi_id', '=', 'k.id')
                    ->where('pt.year', '=', $year);
            })
            ->select([
                's.sector_name',
                'c.name as commitment_name',
                'c.status as commitment_status',
                'd.deliverable',
                'k.kpi',
                'k.unit_of_measurement',
                'kt.target as target_value',
                'pt.actual_value',
                'pt.remarks',
                'pt.confirmation_status',
                'pt.tracking_date'
            ])
            ->orderBy('s.sector_name')
            ->orderBy('c.name')
            ->orderBy('d.deliverable')
            ->orderBy('k.kpi')
            ->get();

        $result = [];
        $operationCounter = 1;
        $currentSector = '';
        $currentCommitment = '';

        foreach ($reportData as $data) {
            // Add sector header if it's a new sector
            if ($data->sector_name !== $currentSector) {
                $currentSector = $data->sector_name;
                $result[] = [
                    'operation_number' => '',
                    'deliverable_description' => $currentSector,
                    'kpi_description' => '',
                    'result_number' => '',
                    'target_value' => '',
                    'actual_result' => '',
                    'performance_ratio' => '',
                    'adjusted_performance' => '',
                    'evidence' => '',
                    'notes' => ''
                ];

                // Add commitment header
                $currentCommitment = $data->commitment_name;
                $result[] = [
                    'operation_number' => '',
                    'deliverable_description' => $data->commitment_name,
                    'kpi_description' => '',
                    'result_number' => '',
                    'target_value' => '',
                    'actual_result' => '',
                    'performance_ratio' => '',
                    'adjusted_performance' => '',
                    'evidence' => '',
                    'notes' => ''
                ];
            } elseif ($data->commitment_name !== $currentCommitment) {
                // Add commitment header if it's a new commitment in the same sector
                $currentCommitment = $data->commitment_name;
                $result[] = [
                    'operation_number' => '',
                    'deliverable_description' => $data->commitment_name,
                    'kpi_description' => '',
                    'result_number' => '',
                    'target_value' => '',
                    'actual_result' => '',
                    'performance_ratio' => '',
                    'adjusted_performance' => '',
                    'evidence' => '',
                    'notes' => ''
                ];
            }

            // Calculate performance metrics
            $target = $data->target_value ?: 0;
            $actual = $data->actual_value ?: 0;

            $performanceRatio = 0;
            $adjustedPerformance = 0;

            if ($target > 0 && is_numeric($actual)) {
                $performanceRatio = ($actual / $target) * 100;
                $adjustedPerformance = $performanceRatio;
            }

            // Add KPI data row
            $result[] = [
                'operation_number' => $operationCounter,
                'deliverable_description' => $data->deliverable,
                'kpi_description' => $data->kpi . ' (' . $data->unit_of_measurement . ')',
                'result_number' => $operationCounter,
                'target_value' => $target,
                'actual_result' => $actual,
                'performance_ratio' => $performanceRatio > 0 ? number_format($performanceRatio, 2) . '%' : 'NA',
                'adjusted_performance' => $adjustedPerformance > 0 ? number_format($adjustedPerformance, 2) . '%' : 'NA',
                'evidence' => $data->remarks ?: '',
                'notes' => $data->confirmation_status ?: ''
            ];

            $operationCounter++;
        }

        return $result;
    }

    private function getSectorSummaryData($year)
    {
        // Get sector summary data with performance counts
        $sectorSummary = DB::table('sectors as s')
            ->leftJoin('commitments as c', 'c.sector_id', '=', 's.id')
            ->leftJoin('deliverables as d', 'd.commitment_id', '=', 'c.id')
            ->leftJoin('kpis as k', 'k.deliverable_id', '=', 'd.id')
            ->leftJoin('kpi_targets as kt', function ($join) use ($year) {
                $join->on('kt.kpi_id', '=', 'k.id')
                    ->where('kt.year', '=', $year);
            })
            ->leftJoin('performance_trackings as pt', function ($join) use ($year) {
                $join->on('pt.kpi_id', '=', 'k.id')
                    ->where('pt.year', '=', $year);
            })
            ->select([
                's.sector_name',
                DB::raw('COUNT(DISTINCT c.id) as commitment_count'),
                DB::raw('COUNT(DISTINCT d.id) as output_count'),
                DB::raw('COUNT(DISTINCT k.id) as result_count')
            ])
            ->groupBy('s.id', 's.sector_name')
            ->get();

        $result = [];
        $sn = 1;
        foreach ($sectorSummary as $sector) {
            // Calculate performance counts for this sector
            $performanceCounts = $this->getSectorPerformanceCounts($sector->sector_name, $year);

            $result[] = [
                'sn' => $sn++,
                'sector_name' => $sector->sector_name,
                'commitment_count' => $sector->commitment_count,
                'output_count' => $sector->output_count,
                'result_count' => $sector->result_count,
                'exceptional_count' => $performanceCounts['exceptional'],
                'above_expectation_count' => $performanceCounts['above_expectation'],
                'meets_expectation_count' => $performanceCounts['meets_expectation'],
                'needs_improvement_count' => $performanceCounts['needs_improvement'],
                'below_minimum_count' => $performanceCounts['below_minimum'],
                'not_assessed_count' => $performanceCounts['not_assessed'],
                'overall_performance' => $performanceCounts['overall_performance'],
                'performance_rating' => $performanceCounts['performance_rating']
            ];
        }
        return $result;
    }

    private function getSectorPerformanceCounts($sectorName, $year)
    {
        // Get all KPIs for the sector to calculate not assessed count
        $allKpis = DB::table('sectors as s')
            ->join('commitments as c', 'c.sector_id', '=', 's.id')
            ->join('deliverables as d', 'd.commitment_id', '=', 'c.id')
            ->join('kpis as k', 'k.deliverable_id', '=', 'd.id')
            ->leftJoin('kpi_targets as kt', function ($join) use ($year) {
                $join->on('kt.kpi_id', '=', 'k.id')
                    ->where('kt.year', '=', $year);
            })
            ->leftJoin('performance_trackings as pt', function ($join) use ($year) {
                $join->on('pt.kpi_id', '=', 'k.id')
                    ->where('pt.year', '=', $year);
            })
            ->select([
                'kt.target',
                'pt.actual_value'
            ])
            ->where('s.sector_name', $sectorName)
            ->whereNotNull('kt.target')
            ->get();

        // Get performance data for assessed KPIs
        $performanceData = $allKpis->whereNotNull('actual_value');

        $counts = [
            'exceptional' => 0,
            'above_expectation' => 0,
            'meets_expectation' => 0,
            'needs_improvement' => 0,
            'below_minimum' => 0,
            'not_assessed' => 0,
            'overall_performance' => 0,
            'performance_rating' => ''
        ];

        // Calculate not assessed count
        $counts['not_assessed'] = $allKpis->whereNull('actual_value')->count();

        // Calculate performance counts for assessed KPIs
        $totalAssessed = 0;
        $totalPerformance = 0;

        foreach ($performanceData as $data) {
            if ($data->target > 0 && is_numeric($data->actual_value)) {
                $ratio = ($data->actual_value / $data->target) * 100;
                $totalAssessed++;
                $totalPerformance += $ratio;

                if ($ratio >= 100) {
                    $counts['exceptional']++;
                } elseif ($ratio >= 70) {
                    $counts['above_expectation']++;
                } elseif ($ratio >= 60) {
                    $counts['meets_expectation']++;
                } elseif ($ratio >= 40) {
                    $counts['needs_improvement']++;
                } else {
                    $counts['below_minimum']++;
                }
            }
        }

        // Calculate overall performance
        if ($totalAssessed > 0) {
            $counts['overall_performance'] = $totalPerformance / $totalAssessed;

            // Determine performance rating
            if ($counts['overall_performance'] >= 100) {
                $counts['performance_rating'] = 'Exceptional';
            } elseif ($counts['overall_performance'] >= 70) {
                $counts['performance_rating'] = 'Above Expectation';
            } elseif ($counts['overall_performance'] >= 60) {
                $counts['performance_rating'] = 'Meets Expectation';
            } elseif ($counts['overall_performance'] >= 40) {
                $counts['performance_rating'] = 'Needs Improvement';
            } else {
                $counts['performance_rating'] = 'Below Minimum';
            }
        }

        return $counts;
    }

    private function createOverallSummarySheet($spreadsheet, $year, $sectorOverallAverageRows = [])
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Overall Summary');

        // 1. Main Title (Row 1)
        $sheet->setCellValue('A1', 'Performance Delivery Coordination Unit (PDCU), Office of the Executive Governor');
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getFont()->setName('Agency FB');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension('1')->setRowHeight(30);

        // 2. Subtitle (Row 2)
        $sheet->setCellValue('A2', 'January to December ' . $year . ' MDA/Sector Summary of Performance on Commitments');
        $sheet->mergeCells('A2:M2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getFont()->setName('Arial Narrow');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A2')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension('2')->setRowHeight(25);

        // 3. Row 3-5 Merged Cells
        $sheet->setCellValue('A3', 'S/N');
        $sheet->mergeCells('A3:A5');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A3')->getAlignment()->setWrapText(true);

        $sheet->setCellValue('B3', 'Ministries and Agencies');
        $sheet->mergeCells('B3:B5');
        $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B3')->getAlignment()->setWrapText(true);

        $sheet->setCellValue('C3', 'Commitments');
        $sheet->mergeCells('C3:C5');
        $sheet->getStyle('C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('C3')->getAlignment()->setWrapText(true);

        $sheet->setCellValue('D3', 'No. of Outputs');
        $sheet->mergeCells('D3:D5');
        $sheet->getStyle('D3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('D3')->getAlignment()->setWrapText(true);

        $sheet->setCellValue('E3', 'No. of Results to be Delivered');
        $sheet->mergeCells('E3:E5');
        $sheet->getStyle('E3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('E3')->getAlignment()->setWrapText(true);

        // 4. Performance Group Header (Row 3-4)
        $sheet->setCellValue('F3', 'Performance for Each Result');
        $sheet->mergeCells('F3:I3');
        $sheet->getStyle('F3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('F3')->getAlignment()->setWrapText(true);

        $sheet->setCellValue('F4', 'Exceptional');
        $sheet->setCellValue('G4', 'Above Expectation');
        $sheet->setCellValue('H4', 'Meets Expectation');
        $sheet->setCellValue('I4', 'Needs Improvement');

        $sheet->setCellValue('J3', 'Below Minimum Expectation');
        $sheet->mergeCells('J3:J4');
        $sheet->getStyle('J3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('J3')->getAlignment()->setWrapText(true);

        $sheet->setCellValue('K3', 'Not Assessed');
        $sheet->mergeCells('K3:K4');
        $sheet->getStyle('K3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('K3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('K3')->getAlignment()->setWrapText(true);

        // 5. Overall Performance Header (Row 3-4)
        $sheet->setCellValue('L3', 'Overall Performance');
        $sheet->mergeCells('L3:M4');
        $sheet->getStyle('L3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('L3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('L3')->getAlignment()->setWrapText(true);

        // 6. Row 5 Descriptions
        $sheet->setCellValue('F5', 'Above 100%');
        $sheet->setCellValue('G5', '70%-100%');
        $sheet->setCellValue('H5', '60%-69%');
        $sheet->setCellValue('I5', '40%-59%');
        $sheet->setCellValue('J5', 'Below 40%');
        $sheet->setCellValue('K5', 'N/A');
        $sheet->setCellValue('L5', 'Performance');
        $sheet->setCellValue('M5', 'Rating');

        // 7. Styling - Make all header text bold and center-aligned
        $sheet->getStyle('A1:M5')->getFont()->setBold(true);
        $sheet->getStyle('A1:M5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:M5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1:M5')->getAlignment()->setWrapText(true);

        // Set row heights - minimum 25 for all rows
        $sheet->getRowDimension('1')->setRowHeight(28); // Main title
        $sheet->getRowDimension('2')->setRowHeight(25); // Subtitle
        $sheet->getRowDimension('3')->setRowHeight(25); // Merged headers
        $sheet->getRowDimension('4')->setRowHeight(40); // Merged headers (keep larger for merged content)
        $sheet->getRowDimension('5')->setRowHeight(25); // Sub-labels

        // Get all sectors for the overall summary
        $sectors = DB::table('sectors')->orderBy('sector_name')->get();

        $row = 7; // Start data from row 7 after headers
        $iteration = 1; // Loop iteration counter

        foreach ($sectors as $sector) {
            // Cell A: Loop iteration number
            $sheet->setCellValue('A' . $row, $iteration);

            // Cell B: Sector name with text wrapping
            $sheet->setCellValue('B' . $row, $sector->sector_name);
            $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('B' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            // Cell C: Number of commitments for that sector in the given year
            $commitmentCount = DB::table('commitments')->where('sector_id', $sector->id)->count();
            $sheet->setCellValue('C' . $row, $commitmentCount > 0 ? $commitmentCount : '-');

            // Cell D: Number of deliverables across all commitments for that sector in that year
            $deliverableCount = DB::table('commitments as c')
                ->join('deliverables as d', 'd.commitment_id', '=', 'c.id')
                ->where('c.sector_id', $sector->id)
                ->count();
            $sheet->setCellValue('D' . $row, $deliverableCount > 0 ? $deliverableCount : '-');

            // Cell E: Total number of KPIs across all deliverables and commitments for that sector in that year
            $kpiCount = DB::table('commitments as c')
                ->join('deliverables as d', 'd.commitment_id', '=', 'c.id')
                ->join('kpis as k', 'k.deliverable_id', '=', 'd.id')
                ->where('c.sector_id', $sector->id)
                ->count();
            $sheet->setCellValue('E' . $row, $kpiCount > 0 ? $kpiCount : '-');

            // Get performance data for this sector - aggregate by KPI to avoid duplicates
            $performanceData = DB::table('commitments as c')
                ->join('deliverables as d', 'd.commitment_id', '=', 'c.id')
                ->join('kpis as k', 'k.deliverable_id', '=', 'd.id')
                ->leftJoin('kpi_targets as kt', function ($join) use ($year) {
                    $join->on('kt.kpi_id', '=', 'k.id')
                        ->where('kt.year', '=', $year);
                })
                ->leftJoin('performance_trackings as pt', function ($join) use ($year) {
                    $join->on('pt.kpi_id', '=', 'k.id')
                        ->where(function ($query) use ($year) {
                            $query->where('pt.year', '=', $year)
                                ->orWhere('pt.year', '=', 0); // Include records with year = 0
                        });
                })
                ->select([
                    'k.id as kpi_id',
                    'kt.target',
                    DB::raw('SUM(CAST(pt.actual_value AS DECIMAL(10,2))) as total_actual_value')
                ])
                ->where('c.sector_id', $sector->id)
                ->whereNotNull('kt.target')
                ->where('kt.target', '!=', '') // Exclude empty targets
                ->groupBy('k.id', 'kt.target')
                ->get();

            // Cell F: Count of KPIs with performance tracking above 100%
            $above100Count = 0;
            // Cell G: Count of KPIs with performance tracking between 70%-100%
            $between70to100Count = 0;
            // Cell H: Count of KPIs with performance tracking between 60%-69%
            $between60to69Count = 0;
            // Cell I: Count of KPIs with performance tracking between 40%-59%
            $between40to59Count = 0;
            // Cell J: Count of KPIs with performance tracking below 40%
            $below40Count = 0;
            // Cell K: Count of KPIs with no performance tracking records
            $noTrackingCount = 0;

            // Variables for calculating average performance
            $totalAssessed = 0;
            $totalPerformance = 0;

            // Get total KPIs for this sector to calculate not assessed count
            $totalKpis = DB::table('commitments as c')
                ->join('deliverables as d', 'd.commitment_id', '=', 'c.id')
                ->join('kpis as k', 'k.deliverable_id', '=', 'd.id')
                ->leftJoin('kpi_targets as kt', function ($join) use ($year) {
                    $join->on('kt.kpi_id', '=', 'k.id')
                        ->where('kt.year', '=', $year);
                })
                ->where('c.sector_id', $sector->id)
                ->whereNotNull('kt.target')
                ->where('kt.target', '!=', '')
                ->count();

            foreach ($performanceData as $data) {
                // Convert target and actual_value to numeric values, handling string values
                $target = is_numeric($data->target) ? (float)$data->target : 0;
                $actualValue = is_numeric($data->total_actual_value) ? (float)$data->total_actual_value : 0;

                if ($target > 0 && $actualValue >= 0) {
                    $ratio = ($actualValue / $target) * 100;
                    $totalAssessed++;
                    $totalPerformance += $ratio;

                    if ($ratio > 100) {
                        $above100Count++;
                    } elseif ($ratio >= 70) {
                        $between70to100Count++;
                    } elseif ($ratio >= 60) {
                        $between60to69Count++;
                    } elseif ($ratio >= 40) {
                        $between40to59Count++;
                    } else {
                        $below40Count++;
                    }
                } else {
                    $noTrackingCount++;
                }
            }

            $sheet->setCellValue('F' . $row, $above100Count > 0 ? $above100Count : '-');
            $sheet->setCellValue('G' . $row, $between70to100Count > 0 ? $between70to100Count : '-');
            $sheet->setCellValue('H' . $row, $between60to69Count > 0 ? $between60to69Count : '-');
            $sheet->setCellValue('I' . $row, $between40to59Count > 0 ? $between40to59Count : '-');
            $sheet->setCellValue('J' . $row, $below40Count > 0 ? $below40Count : '-');
            $notAssessedCount = $totalKpis - $totalAssessed;
            $sheet->setCellValue('K' . $row, $notAssessedCount > 0 ? $notAssessedCount : '-'); // Not assessed = total KPIs - assessed KPIs

            // Cell L: Insert a formula that references the last H cell in the corresponding sector sheet
            if ($sector && isset($sectorOverallAverageRows[$sector->description])) {
                $overallAverageRow = $sectorOverallAverageRows[$sector->description];
                $sheet->setCellValue('L' . $row, "='$sector->description'!H" . $overallAverageRow);
            } else {
                // Fallback if mapping not found
                $sheet->setCellValue('L' . $row, 0);
            }

            // Apply Arial Narrow font, size 12 to all cells
            for ($col = 'A'; $col <= 'L'; $col++) {
                $sheet->getStyle($col . $row)->getFont()->setName('Arial Narrow');
                $sheet->getStyle($col . $row)->getFont()->setSize(12);
                $sheet->getStyle($col . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }

            // Center align numeric cells (A, C, D, E, F, G, H, I, J, K)
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('J' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('K' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Format percentage cell L
            $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE);

            // Set minimum row height for summary data rows
            $sheet->getRowDimension($row)->setRowHeight(25);

            $row++;
            $iteration++;
        }

        // Add summary row after all data rows
        $summaryRow = $row;

        // Cell A: Empty
        $sheet->setCellValue('A' . $summaryRow, '');

        // Cell B: Summary label
        $sheet->setCellValue('B' . $summaryRow, '');

        // Cells C-K: Sum formulas
        $dataStartRow = 7; // First data row
        $dataEndRow = $row - 1; // Last data row

        $sheet->setCellValue('C' . $summaryRow, "=SUM(C{$dataStartRow}:C{$dataEndRow})");
        $sheet->setCellValue('D' . $summaryRow, "=SUM(D{$dataStartRow}:D{$dataEndRow})");
        $sheet->setCellValue('E' . $summaryRow, "=SUM(E{$dataStartRow}:E{$dataEndRow})");
        $sheet->setCellValue('F' . $summaryRow, "=SUM(F{$dataStartRow}:F{$dataEndRow})");
        $sheet->setCellValue('G' . $summaryRow, "=SUM(G{$dataStartRow}:G{$dataEndRow})");
        $sheet->setCellValue('H' . $summaryRow, "=SUM(H{$dataStartRow}:H{$dataEndRow})");
        $sheet->setCellValue('I' . $summaryRow, "=SUM(I{$dataStartRow}:I{$dataEndRow})");
        $sheet->setCellValue('J' . $summaryRow, "=SUM(J{$dataStartRow}:J{$dataEndRow})");
        $sheet->setCellValue('K' . $summaryRow, "=SUM(K{$dataStartRow}:K{$dataEndRow})");

        // Cell L: Average formula
        $sheet->setCellValue('L' . $summaryRow, "=AVERAGE(L{$dataStartRow}:L{$dataEndRow})");

        // Apply formatting to summary row
        $sheet->getStyle('A' . $summaryRow . ':L' . $summaryRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $summaryRow . ':L' . $summaryRow)->getFont()->setName('Arial Narrow');
        $sheet->getStyle('A' . $summaryRow . ':L' . $summaryRow)->getFont()->setSize(12);

        // Center align numeric cells (C-K)
        for ($col = 'C'; $col <= 'K'; $col++) {
            $sheet->getStyle($col . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Format percentage cell L
        $sheet->getStyle('L' . $summaryRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE);

        // Set row height for summary row
        $sheet->getRowDimension($summaryRow)->setRowHeight(25);

        // Add background color to summary row
        $sheet->getStyle('A' . $summaryRow . ':L' . $summaryRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6F3FF');

        // Style headers with background color
        $sheet->getStyle('A3:M5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCE6F1');

        // Apply the same background color to row 6
        $sheet->getStyle('A6:M6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCE6F1');

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(6);   // S/N
        $sheet->getColumnDimension('B')->setWidth(45);  // Ministries and Agencies
        $sheet->getColumnDimension('C')->setWidth(12);  // Commitments
        $sheet->getColumnDimension('D')->setWidth(12);  // No. of Outputs
        $sheet->getColumnDimension('E')->setWidth(12);  // No. of Results to be Delivered
        $sheet->getColumnDimension('F')->setWidth(12);  // Exceptional
        $sheet->getColumnDimension('G')->setWidth(12);  // Above Expectation
        $sheet->getColumnDimension('H')->setWidth(12);  // Meets Expectation
        $sheet->getColumnDimension('I')->setWidth(12);  // Needs Improvement
        $sheet->getColumnDimension('J')->setWidth(12);  // Below Minimum Expectation
        $sheet->getColumnDimension('K')->setWidth(12);  // Not Assessed
        $sheet->getColumnDimension('L')->setWidth(12);  // Performance
        $sheet->getColumnDimension('M')->setWidth(20);  // Rating

        // Add borders to header cells (rows 1-5)
        $sheet->getStyle('A1:M5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Remove all borders from the summary data rows (row 6 onwards)
        $lastRow = $row - 1;
        if ($lastRow > 5) {
            $sheet->getStyle('A6:M' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
        }
    }

    private function createGrandSummarySheet($spreadsheet, $year, $sectorOverallAverageRows = [])
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Grand Summary-Sector_MDAs+');

        // Row 1: Top Title
        $sheet->setCellValue('A1', 'Performance Delivery Coordination Unit (PDCU), Office of the Executive Governor');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getFont()->setName('Agency FB');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension('1')->setRowHeight(30);

        // Row 2: Subtitle
        $sheet->setCellValue('A2', $year . ' Fiscal Year Snapshot View of MDA/Sector Performance (January to December');
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2')->getFont()->setName('Agency FB');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A2')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension('2')->setRowHeight(30);

        // Row 3: Column headers
        // Cells A-E span rows 3-4
        $sheet->setCellValue('A3', 'S/N');
        $sheet->mergeCells('A3:A4');
        $sheet->getStyle('A3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A3')->getAlignment()->setWrapText(true);

        $sheet->setCellValue('B3', 'Names of MDAs/Sectors');
        $sheet->mergeCells('B3:B4');
        $sheet->getStyle('B3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B3')->getAlignment()->setWrapText(true);

        $sheet->setCellValue('C3', 'No. of Commitments');
        $sheet->mergeCells('C3:C4');
        $sheet->getStyle('C3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C3')->getAlignment()->setWrapText(true);

        $sheet->setCellValue('D3', 'No. of Outputs');
        $sheet->mergeCells('D3:D4');
        $sheet->getStyle('D3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('D3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D3')->getAlignment()->setWrapText(true);

        $sheet->setCellValue('E3', 'No of Results to be Delivered');
        $sheet->mergeCells('E3:E4');
        $sheet->getStyle('E3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('E3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E3')->getAlignment()->setWrapText(true);

        // Cells F3-H3 merged for 'Overall Performance'
        $sheet->setCellValue('F3', 'Overall Performance');
        $sheet->mergeCells('F3:H3');
        $sheet->getStyle('F3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('F3')->getAlignment()->setWrapText(true);

        // Row 4: Sub-headers for Overall Performance
        $sheet->setCellValue('F4', 'Performance at Mid-Year');
        $sheet->setCellValue('G4', 'Fully Performance');
        $sheet->setCellValue('H4', 'Fully Performance Rating');

        // Set row heights for header rows
        $sheet->getRowDimension('1')->setRowHeight(30); // Title row
        $sheet->getRowDimension('2')->setRowHeight(30); // Subtitle row
        $sheet->getRowDimension('3')->setRowHeight(22); // Header row
        $sheet->getRowDimension('4')->setRowHeight(30); // Sub-header row

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(5);   // S/N
        $sheet->getColumnDimension('B')->setWidth(45);  // Names of MDAs/Sectors
        $sheet->getColumnDimension('C')->setWidth(10);  // No. of Commitments
        $sheet->getColumnDimension('D')->setWidth(10);  // No. of Outputs
        $sheet->getColumnDimension('E')->setWidth(10);  // No of Results to be Delivered
        $sheet->getColumnDimension('F')->setWidth(15);  // Performance at Mid-Year
        $sheet->getColumnDimension('G')->setWidth(15);  // Fully Performance
        $sheet->getColumnDimension('H')->setWidth(25);  // Fully Performance Rating

        // Apply text wrapping and centering to all header cells
        for ($col = 'A'; $col <= 'H'; $col++) {
            for ($row = 3; $row <= 4; $row++) {
                $sheet->getStyle($col . $row)->getAlignment()->setWrapText(true);
                $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($col . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle($col . $row)->getFont()->setName('Agency FB');
                $sheet->getStyle($col . $row)->getFont()->setSize(10);
            }
        }

        // Style headers
        $sheet->getStyle('A1:H4')->getFont()->setBold(true);

        // Apply background color to all header cells (rows 3-4)
        $sheet->getStyle('A3:H4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCE6F1');

        $sheet->getStyle('A3:H4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A3:H4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Get grand summary data
        $grandSummary = $this->getGrandSummaryData($year);

        $row = 5; // Start data from row 5 after headers
        $iteration = 1; // Loop iteration counter

        foreach ($grandSummary as $summary) {
            // Cell A: Set the value to the loop iteration number
            $sheet->setCellValue('A' . $row, $iteration);

            // Cell B: Set the value to the sector name, align the text to the left
            $sheet->setCellValue('B' . $row, $summary['sector_name']);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('B' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            // Cell C: Set the value to the number of commitments for that sector
            $sheet->setCellValue('C' . $row, $summary['commitment_count']);

            // Cell D: Set the value to the total number of deliverables for all commitments for that sector
            $sheet->setCellValue('D' . $row, $summary['deliverable_count']);

            // Cell E: Set the value to the total number of KPIs for all deliverables for all commitments for that sector
            $sheet->setCellValue('E' . $row, $summary['kpi_count']);

            // Cell F: Leave empty
            $sheet->setCellValue('F' . $row, '');

            // Cell G: Insert a formula that references the last H cell in the corresponding sector sheet
            $sectorSheetName = $summary['sector_name'];

            // Get the sector description (which is used as the sheet name) from the database
            $sector = DB::table('sectors')->where('sector_name', $sectorSheetName)->first();
            if ($sector && isset($sectorOverallAverageRows[$sector->description])) {
                $overallAverageRow = $sectorOverallAverageRows[$sector->description];
                $sheet->setCellValue('G' . $row, "='$sector->description'!H" . $overallAverageRow);
            } else {
                // Fallback if mapping not found
                $sheet->setCellValue('G' . $row, 0);
            }

            // Apply the font "Agency FB" with size 15 to all these cells (A to G)
            for ($col = 'A'; $col <= 'G'; $col++) {
                $sheet->getStyle($col . $row)->getFont()->setName('Agency FB');
                $sheet->getStyle($col . $row)->getFont()->setSize(15);
                $sheet->getStyle($col . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }

            // Center align numeric cells (A, C, D, E)
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Format percentage cell G
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE);

            $row++;
            $iteration++;
        }

        // Add summary row after the last sector/ministry row
        $summaryRow = $row;

        // Cell A: Set to (last loop iteration + 1)
        $sheet->setCellValue('A' . $summaryRow, $iteration);

        // Cell B: Set to 'Overall State Performance'
        $sheet->setCellValue('B' . $summaryRow, 'Overall State Performance');
        $sheet->getStyle('B' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B' . $summaryRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Cells C, D, and E: Set to the sum of the values above in their respective columns
        $dataStartRow = 5; // Start of data rows
        $dataEndRow = $row - 1; // End before this summary row
        $sheet->setCellValue('C' . $summaryRow, '=SUM(C' . $dataStartRow . ':C' . $dataEndRow . ')');
        $sheet->setCellValue('D' . $summaryRow, '=SUM(D' . $dataStartRow . ':D' . $dataEndRow . ')');
        $sheet->setCellValue('E' . $summaryRow, '=SUM(E' . $dataStartRow . ':E' . $dataEndRow . ')');

        // Cells F and G: Set to the average of the values above in their respective columns
        // Use IF formula to handle division by zero
        $sheet->setCellValue('F' . $summaryRow, '=IF(COUNT(F' . $dataStartRow . ':F' . $dataEndRow . ')>0,AVERAGE(F' . $dataStartRow . ':F' . $dataEndRow . '),0)');
        $sheet->setCellValue('G' . $summaryRow, '=IF(COUNT(G' . $dataStartRow . ':G' . $dataEndRow . ')>0,AVERAGE(G' . $dataStartRow . ':G' . $dataEndRow . '),0)');

        // Apply the same font styling (Agency FB, size 15) to all cells
        for ($col = 'A'; $col <= 'G'; $col++) {
            $sheet->getStyle($col . $summaryRow)->getFont()->setName('Agency FB');
            $sheet->getStyle($col . $summaryRow)->getFont()->setSize(15);
            $sheet->getStyle($col . $summaryRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }

        // Center align numeric cells (A, C, D, E)
        $sheet->getStyle('A' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Format percentage cells F and G
        $sheet->getStyle('F' . $summaryRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE);
        $sheet->getStyle('G' . $summaryRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE);

        // Make the summary row bold to distinguish it
        $sheet->getStyle('A' . $summaryRow . ':G' . $summaryRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $summaryRow . ':G' . $summaryRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');

        // Add borders to the entire data range (including summary row)
        $lastRow = $summaryRow;
        if ($lastRow > 4) {
            $sheet->getStyle('A3:H' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        // Add borders to the header block (A1:H4)
        $sheet->getStyle('A1:H4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    private function getGrandSummaryData($year)
    {
        // Get grand summary data - use LEFT JOIN to include all sectors even if they don't have complete data
        $grandSummary = DB::table('sectors as s')
            ->leftJoin('commitments as c', 'c.sector_id', '=', 's.id')
            ->leftJoin('deliverables as d', 'd.commitment_id', '=', 'c.id')
            ->leftJoin('kpis as k', 'k.deliverable_id', '=', 'd.id')
            ->leftJoin('kpi_targets as kt', function ($join) use ($year) {
                $join->on('kt.kpi_id', '=', 'k.id')
                    ->where('kt.year', '=', $year);
            })
            ->leftJoin('performance_trackings as pt', function ($join) use ($year) {
                $join->on('pt.kpi_id', '=', 'k.id')
                    ->where('pt.year', '=', $year);
            })
            ->select([
                's.sector_name',
                DB::raw('COUNT(DISTINCT c.id) as commitment_count'),
                DB::raw('COUNT(DISTINCT d.id) as deliverable_count'),
                DB::raw('COUNT(DISTINCT k.id) as kpi_count'),
                DB::raw('AVG(CASE WHEN pt.actual_value IS NOT NULL THEN (pt.actual_value / kt.target) * 100 ELSE 0 END) as average_performance'),
                DB::raw('CASE WHEN AVG(CASE WHEN pt.actual_value IS NOT NULL THEN (pt.actual_value / kt.target) * 100 ELSE 0 END) >= 150 THEN \'Exceptional (Distinction)\' WHEN AVG(CASE WHEN pt.actual_value IS NOT NULL THEN (pt.actual_value / kt.target) * 100 ELSE 0 END) >= 50 THEN \'Above Expectation (Very Good)\' WHEN AVG(CASE WHEN pt.actual_value IS NOT NULL THEN (pt.actual_value / kt.target) * 100 ELSE 0 END) >= 30 THEN \'Meets Expectation\' WHEN AVG(CASE WHEN pt.actual_value IS NOT NULL THEN (pt.actual_value / kt.target) * 100 ELSE 0 END) >= 20 THEN \'Needs Improvement (Fair)\' ELSE \'Below Minimum Expectation\' END as performance_rating')
            ])
            ->groupBy('s.sector_name')
            ->get();

        $result = [];
        $sn = 1;
        foreach ($grandSummary as $summary) {
            $result[] = [
                'sn' => $sn++,
                'sector_name' => $summary->sector_name,
                'commitment_count' => $summary->commitment_count,
                'deliverable_count' => $summary->deliverable_count,
                'kpi_count' => $summary->kpi_count,
                'average_performance' => $summary->average_performance,
                'performance_rating' => $summary->performance_rating
            ];
        }
        return $result;
    }

    private function getOverallSummaryData($year)
    {
        // Get overall summary data
        $overallSummary = DB::table('sectors as s')
            ->join('commitments as c', 'c.sector_id', '=', 's.id')
            ->join('deliverables as d', 'd.commitment_id', '=', 'c.id')
            ->join('kpis as k', 'k.deliverable_id', '=', 'd.id')
            ->leftJoin('kpi_targets as kt', function ($join) use ($year) {
                $join->on('kt.kpi_id', '=', 'k.id')
                    ->where('kt.year', '=', $year);
            })
            ->leftJoin('performance_trackings as pt', function ($join) use ($year) {
                $join->on('pt.kpi_id', '=', 'k.id')
                    ->where('pt.year', '=', $year);
            })
            ->select([
                's.sector_name',
                DB::raw('COUNT(DISTINCT c.id) as commitment_count'),
                DB::raw('COUNT(DISTINCT d.id) as deliverable_count'),
                DB::raw('COUNT(DISTINCT k.id) as kpi_count'),
                DB::raw('AVG(CASE WHEN pt.actual_value IS NOT NULL THEN (pt.actual_value / kt.target) * 100 ELSE 0 END) as average_performance'),
                DB::raw('CASE WHEN AVG(CASE WHEN pt.actual_value IS NOT NULL THEN (pt.actual_value / kt.target) * 100 ELSE 0 END) >= 150 THEN \'Exceptional (Distinction)\' WHEN AVG(CASE WHEN pt.actual_value IS NOT NULL THEN (pt.actual_value / kt.target) * 100 ELSE 0 END) >= 50 THEN \'Above Expectation (Very Good)\' WHEN AVG(CASE WHEN pt.actual_value IS NOT NULL THEN (pt.actual_value / kt.target) * 100 ELSE 0 END) >= 30 THEN \'Meets Expectation\' WHEN AVG(CASE WHEN pt.actual_value IS NOT NULL THEN (pt.actual_value / kt.target) * 100 ELSE 0 END) >= 20 THEN \'Needs Improvement (Fair)\' ELSE \'Below Minimum Expectation\' END as performance_rating')
            ])
            ->groupBy('s.sector_name')
            ->get();

        $result = [];
        $sn = 1;
        foreach ($overallSummary as $summary) {
            $result[] = [
                'sn' => $sn++,
                'sector_name' => $summary->sector_name,
                'commitment_count' => $summary->commitment_count,
                'deliverable_count' => $summary->deliverable_count,
                'kpi_count' => $summary->kpi_count,
                'average_performance' => $summary->average_performance,
                'performance_rating' => $summary->performance_rating
            ];
        }
        return $result;
    }

    private function createSectorSummaryDetailsSheet($spreadsheet, $year, $commitmentAverageRows = [])
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Sector_MDAs Summary Details');

        // Row 1: Merged cells A-N
        $sheet->setCellValue('A1', 'Performance Delivery Coordination Unit (PDCU), Office of the Executive Governor');
        $sheet->mergeCells('A1:N1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getFont()->setName('Agency FB');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension('1')->setRowHeight(30);

        // Row 2: Merged cells A-N
        $sheet->setCellValue('A2', 'January to December ' . $year . ' MDA/Sector Summary of Performance on Commitments');
        $sheet->mergeCells('A2:N2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A2')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension('2')->setRowHeight(30);

        // Row 3: Column headers
        // Cells A-D span rows 3-5
        $sheet->setCellValue('A3', 'S/N');
        $sheet->mergeCells('A3:A5');
        $sheet->getStyle('A3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A3')->getAlignment()->setWrapText(true);

        $sheet->setCellValue('B3', 'Commitments');
        $sheet->mergeCells('B3:B5');
        $sheet->getStyle('B3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B3')->getAlignment()->setWrapText(true);

        $sheet->setCellValue('C3', 'No. of Outputs');
        $sheet->mergeCells('C3:C5');
        $sheet->getStyle('C3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C3')->getAlignment()->setWrapText(true);

        $sheet->setCellValue('D3', 'No of Results to be Delivered');
        $sheet->mergeCells('D3:D5');
        $sheet->getStyle('D3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('D3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D3')->getAlignment()->setWrapText(true);

        // Cells E3-J3 merged for 'Performance for Each Result'
        $sheet->setCellValue('E3', 'Performance for Each Result');
        $sheet->mergeCells('E3:J3');
        $sheet->getStyle('E3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('E3')->getAlignment()->setWrapText(true);

        // Cells K3-L4 merged for 'Overall Performance'
        $sheet->setCellValue('K3', 'Overall Performance');
        $sheet->mergeCells('K3:L4');
        $sheet->getStyle('K3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('K3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('K3')->getAlignment()->setWrapText(true);

        // Cell M3-M5 merged for 'r'
        $sheet->setCellValue('M3', 'r');
        $sheet->mergeCells('M3:M5');
        $sheet->getStyle('M3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('M3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('M3')->getAlignment()->setWrapText(true);

        // Cell N3-N5 merged for 'Check'
        $sheet->setCellValue('N3', 'Check');
        $sheet->mergeCells('N3:N5');
        $sheet->getStyle('N3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('N3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('N3')->getAlignment()->setWrapText(true);

        // Row 4: Sub-headers for Performance categories
        $sheet->setCellValue('E4', 'Exceptional');
        $sheet->setCellValue('F4', 'Above Expectation');
        $sheet->setCellValue('G4', 'Meets Expectation');
        $sheet->setCellValue('H4', 'Needs Improvement');
        $sheet->setCellValue('I4', 'Below Minimum Expectation');
        $sheet->setCellValue('J4', 'Not Assessed');

        // Row 5: Performance ranges and ratings
        $sheet->setCellValue('E5', 'Above 100%');
        $sheet->setCellValue('F5', '70% - 100%');
        $sheet->setCellValue('G5', '60% - 69%');
        $sheet->setCellValue('H5', '40% - 59%');
        $sheet->setCellValue('I5', 'Below 40%');
        $sheet->setCellValue('J5', 'N/A');
        $sheet->setCellValue('K5', 'Performance');
        $sheet->setCellValue('L5', 'Rating');

        // Set row heights for header rows
        $sheet->getRowDimension('3')->setRowHeight(25);
        $sheet->getRowDimension('4')->setRowHeight(45);
        $sheet->getRowDimension('5')->setRowHeight(22);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(5);  // S/N
        $sheet->getColumnDimension('B')->setWidth(40); // Commitments
        $sheet->getColumnDimension('C')->setWidth(10); // No. of Outputs
        $sheet->getColumnDimension('D')->setWidth(10); // No of Results to be Delivered
        $sheet->getColumnDimension('E')->setWidth(12); // Exceptional
        $sheet->getColumnDimension('F')->setWidth(12); // Above Expectation
        $sheet->getColumnDimension('G')->setWidth(12); // Meets Expectation
        $sheet->getColumnDimension('H')->setWidth(12); // Needs Improvement
        $sheet->getColumnDimension('I')->setWidth(12); // Below Minimum Expectation
        $sheet->getColumnDimension('J')->setWidth(12); // Not Assessed
        $sheet->getColumnDimension('K')->setWidth(15); // Performance
        $sheet->getColumnDimension('L')->setWidth(15); // Rating
        $sheet->getColumnDimension('M')->setWidth(8);  // r
        $sheet->getColumnDimension('N')->setWidth(10); // Check

        // Apply text wrapping and centering to all header cells
        for ($col = 'A'; $col <= 'N'; $col++) {
            for ($row = 3; $row <= 5; $row++) {
                $sheet->getStyle($col . $row)->getAlignment()->setWrapText(true);
                $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($col . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }
        }

        // Get sector summary data with performance calculations
        $sectorSummary = DB::table('sectors as s')
            ->join('commitments as c', 'c.sector_id', '=', 's.id')
            ->leftJoin('deliverables as d', 'd.commitment_id', '=', 'c.id')
            ->leftJoin('kpis as k', 'k.deliverable_id', '=', 'd.id')
            ->leftJoin('kpi_targets as kt', function ($join) use ($year) {
                $join->on('kt.kpi_id', '=', 'k.id')
                    ->where('kt.year', '=', $year);
            })
            ->leftJoin('performance_trackings as pt', function ($join) use ($year) {
                $join->on('pt.kpi_id', '=', 'k.id')
                    ->where('pt.year', '=', $year);
            })
            ->select([
                's.id as sector_id',
                's.sector_name',
                's.description',
                'c.id as commitment_id',
                'c.name as commitment_name',
                DB::raw('COUNT(DISTINCT d.id) as deliverable_count'),
                DB::raw('COUNT(DISTINCT k.id) as kpi_count'),
                DB::raw('SUM(CASE WHEN kt.target > 0 AND pt.actual_value > 0 AND (pt.actual_value / kt.target) > 1 THEN 1 ELSE 0 END) as exceptional_count'),
                DB::raw('SUM(CASE WHEN kt.target > 0 AND pt.actual_value > 0 AND (pt.actual_value / kt.target) >= 0.7 AND (pt.actual_value / kt.target) <= 1 THEN 1 ELSE 0 END) as above_expectation_count'),
                DB::raw('SUM(CASE WHEN kt.target > 0 AND pt.actual_value > 0 AND (pt.actual_value / kt.target) >= 0.6 AND (pt.actual_value / kt.target) < 0.7 THEN 1 ELSE 0 END) as meets_expectation_count'),
                DB::raw('SUM(CASE WHEN kt.target > 0 AND pt.actual_value > 0 AND (pt.actual_value / kt.target) >= 0.4 AND (pt.actual_value / kt.target) < 0.6 THEN 1 ELSE 0 END) as needs_improvement_count'),
                DB::raw('SUM(CASE WHEN kt.target > 0 AND pt.actual_value > 0 AND (pt.actual_value / kt.target) < 0.4 THEN 1 ELSE 0 END) as below_minimum_count'),
                DB::raw('SUM(CASE WHEN pt.actual_value IS NULL OR pt.actual_value = 0 THEN 1 ELSE 0 END) as not_assessed_count')
            ])
            ->groupBy('s.id', 's.sector_name', 's.description', 'c.id', 'c.name')
            ->orderBy('s.sector_name')
            ->orderBy('c.id')
            ->get();

        // Group data by sector
        $sectors = $sectorSummary->groupBy('sector_id');

        $row = 6; // Start data from row 6 after headers

        foreach ($sectors as $sectorId => $sectorCommitments) {
            $sector = $sectorCommitments->first();

            // Sector Row
            $sheet->setCellValue('B' . $row, $sector->sector_name);
            $sheet->getStyle('B' . $row)->getFont()->setBold(true);
            $sheet->getStyle('B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6F3FF');
            $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('B' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $row++;

            foreach ($sectorCommitments as $commitment) {
                // Commitment Row
                $sheet->setCellValue('B' . $row, $commitment->commitment_name);
                $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true);
                $sheet->getStyle('B' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('C' . $row, $commitment->deliverable_count);
                $sheet->setCellValue('D' . $row, $commitment->kpi_count);
                $sheet->setCellValue('E' . $row, $commitment->exceptional_count > 0 ? $commitment->exceptional_count : '-');
                $sheet->setCellValue('F' . $row, $commitment->above_expectation_count > 0 ? $commitment->above_expectation_count : '-');
                $sheet->setCellValue('G' . $row, $commitment->meets_expectation_count > 0 ? $commitment->meets_expectation_count : '-');
                $sheet->setCellValue('H' . $row, $commitment->needs_improvement_count > 0 ? $commitment->needs_improvement_count : '-');
                $sheet->setCellValue('I' . $row, $commitment->below_minimum_count > 0 ? $commitment->below_minimum_count : '-');
                $sheet->setCellValue('J' . $row, $commitment->not_assessed_count > 0 ? $commitment->not_assessed_count : '-');

                // Formula for cell K - reference the average performance from the sector sheet
                if (isset($commitmentAverageRows[$sector->description][$commitment->commitment_id])) {
                    $averageRow = $commitmentAverageRows[$sector->description][$commitment->commitment_id];
                    $sheet->setCellValue('K' . $row, "='$sector->description'!I" . $averageRow);
                    $sheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE);
                } else {
                    // Fallback if mapping not found
                    $sheet->setCellValue('K' . $row, "N/A");
                }

                // Apply formatting to numeric cells
                for ($col = 'C'; $col <= 'J'; $col++) {
                    $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle($col . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                }

                // Apply header background color to cells E-J and remove borders
                $sheet->getStyle('E' . $row . ':J' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCE6F1');
                $sheet->getStyle('E' . $row . ':J' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);


                $row++;
            }

            // Summary Row for this sector
            $summaryRow = $row;

            // Find the start row for this sector's data
            $sectorStartRow = $row - count($sectorCommitments);

            // Cells C-K: Sum the values above for the given sector, replace 0 with '-'
            $sheet->setCellValue('C' . $summaryRow, "=IF(SUM(C" . $sectorStartRow . ":C" . ($row - 1) . ")>0,SUM(C" . $sectorStartRow . ":C" . ($row - 1) . "),\"-\")");
            $sheet->setCellValue('D' . $summaryRow, "=IF(SUM(D" . $sectorStartRow . ":D" . ($row - 1) . ")>0,SUM(D" . $sectorStartRow . ":D" . ($row - 1) . "),\"-\")");
            $sheet->setCellValue('E' . $summaryRow, "=IF(SUM(E" . $sectorStartRow . ":E" . ($row - 1) . ")>0,SUM(E" . $sectorStartRow . ":E" . ($row - 1) . "),\"-\")");
            $sheet->setCellValue('F' . $summaryRow, "=IF(SUM(F" . $sectorStartRow . ":F" . ($row - 1) . ")>0,SUM(F" . $sectorStartRow . ":F" . ($row - 1) . "),\"-\")");
            $sheet->setCellValue('G' . $summaryRow, "=IF(SUM(G" . $sectorStartRow . ":G" . ($row - 1) . ")>0,SUM(G" . $sectorStartRow . ":G" . ($row - 1) . "),\"-\")");
            $sheet->setCellValue('H' . $summaryRow, "=IF(SUM(H" . $sectorStartRow . ":H" . ($row - 1) . ")>0,SUM(H" . $sectorStartRow . ":H" . ($row - 1) . "),\"-\")");
            $sheet->setCellValue('I' . $summaryRow, "=IF(SUM(I" . $sectorStartRow . ":I" . ($row - 1) . ")>0,SUM(I" . $sectorStartRow . ":I" . ($row - 1) . "),\"-\")");
            $sheet->setCellValue('J' . $summaryRow, "=IF(SUM(J" . $sectorStartRow . ":J" . ($row - 1) . ")>0,SUM(J" . $sectorStartRow . ":J" . ($row - 1) . "),\"-\")");
            $sheet->setCellValue('K' . $summaryRow, "=AVERAGE(K" . $sectorStartRow . ":K" . ($row - 1) . ")");
            $sheet->getStyle('K' . $summaryRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE);

            // Style the summary row
            $sheet->getStyle('C' . $summaryRow . ':K' . $summaryRow)->getFont()->setBold(true);
            $sheet->getStyle('C' . $summaryRow . ':K' . $summaryRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');

            $row++;

            // Add empty row after summary row
            $row++;
        }

        // Style headers
        $sheet->getStyle('A1:N5')->getFont()->setBold(true);

        // Apply background color to all header cells (rows 3-5)
        $sheet->getStyle('A3:N5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCE6F1');

        // Ensure merged cells also get the background color
        $sheet->getStyle('A3:D5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCE6F1'); // S/N, Commitments, No. of Outputs, No of Results
        $sheet->getStyle('E3:J3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCE6F1'); // Performance for Each Result
        $sheet->getStyle('K3:L4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCE6F1'); // Overall Performance
        $sheet->getStyle('M3:M5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCE6F1'); // r
        $sheet->getStyle('N3:N5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCE6F1'); // Check

        $sheet->getStyle('A3:N5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A3:N5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Remove auto-sizing and enforce fixed dimensions
        // Auto-fit columns - REMOVED to prevent stretching
        // foreach (range('A', 'N') as $col) {
        //     $sheet->getColumnDimension($col)->setAutoSize(true);
        // }

        // Add borders to the entire data range (excluding cells E-J)
        $lastRow = $row - 1;
        if ($lastRow > 5) {
            // Add borders to columns A-D, K-N (excluding E-J)
            $sheet->getStyle('A3:D' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle('K3:N' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        // Add borders to the header block (A1:N5)
        $sheet->getStyle('A1:N5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);


    }

    private function createIndividualSectorSheets($spreadsheet, $year)
    {
        $sectors = Sector::all(); // Get all sectors
        $commitmentAverageRows = []; // Track commitment average row numbers
        $sectorOverallAverageRows = []; // Track sector overall average row numbers

        $first = true;
        foreach ($sectors as $sector) {
            if ($first) {
                $sheet = $spreadsheet->getActiveSheet();
                $first = false;
            } else {
                $sheet = $spreadsheet->createSheet();
            }

            $sheet->setTitle($sector->description);

            // Row 1: Merged cells A-I
            $sheet->setCellValue('A1', strtoupper($sector->sector_name));
            $sheet->mergeCells('A1:I1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension('1')->setRowHeight(28);

            // Row 2: Merged cells A-I
            $sheet->setCellValue('A2', 'FULL YEAR  [JANUARY TO DECEMBER ' . $year . '] PERFORMANCE ASSESSMENT');
            $sheet->mergeCells('A2:I2');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension('2')->setRowHeight(28);

            // Row 3: Column headers
            $sheet->setCellValue('A3', 'No. of Ops');
            $sheet->setCellValue('B3', 'Expected Outputs for Delivering the Outcome Targets');
            $sheet->setCellValue('C3', 'Output KPIs');
            $sheet->setCellValue('D3', 'Results No.');
            $sheet->setCellValue('E3', $year . ' Target');
            $sheet->setCellValue('F3', 'Jan. - Dec Results');
            $sheet->setCellValue('G3', 'Performance');
            $sheet->setCellValue('H3', 'Adjusted Performance');
            $sheet->setCellValue('I3', 'Evidences');

            // Style column headers (Row 3) - light bluish background
            $sheet->getStyle('A3:I3')->getFont()->setBold(true);
            $sheet->getStyle('A3:I3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A3:I3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('A3:I3')->getAlignment()->setWrapText(true);
            $sheet->getStyle('A3:I3')->getFill()->setFillType(Fill::FILL_SOLID);
            $sheet->getStyle('A3:I3')->getFill()->getStartColor()->setRGB('B7CDE0'); // Light bluish background
            $sheet->getRowDimension('3')->setRowHeight(45);

            // Row 4: Special header - OUTPUT LEVEL FRAMEWORK
            $sheet->setCellValue('A4', 'OUTPUT LEVEL FRAMEWORK - ' . strtoupper($sector->sector_name));
            $sheet->mergeCells('A4:I4');
            $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A4')->getFont()->getColor()->setRGB('FFFFFF'); // White text
            $sheet->getStyle('A4')->getFill()->setFillType(Fill::FILL_SOLID);
            $sheet->getStyle('A4')->getFill()->getStartColor()->setRGB('004A99'); // Dark bluish background
            $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension('4')->setRowHeight(25);

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(7); // No. of ops
            $sheet->getColumnDimension('B')->setWidth(30); // Expected Outputs
            $sheet->getColumnDimension('C')->setWidth(25); // Output KPIs
            $sheet->getColumnDimension('D')->setWidth(10); // Results No.
            $sheet->getColumnDimension('E')->setWidth(10); // 2024 Target
            $sheet->getColumnDimension('F')->setWidth(10); // Jan. - Dec Results
            $sheet->getColumnDimension('G')->setWidth(10); // Performance
            $sheet->getColumnDimension('H')->setWidth(10); // Adjusted Performance
            $sheet->getColumnDimension('I')->setWidth(28); // Evidences

            // Add borders to the header block (A1:I4)
            $sheet->getStyle('A1:I4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // Get individual sector data with commitments, deliverables, and KPIs
            $sectorData = DB::table('sectors as s')
                ->join('commitments as c', 'c.sector_id', '=', 's.id')
                ->leftJoin('deliverables as d', 'd.commitment_id', '=', 'c.id')
                ->leftJoin('kpis as k', 'k.deliverable_id', '=', 'd.id')
                ->leftJoin('kpi_targets as kt', function ($join) use ($year) {
                    $join->on('kt.kpi_id', '=', 'k.id')
                        ->where('kt.year', '=', $year);
                })
                ->leftJoin('performance_trackings as pt', function ($join) use ($year) {
                    $join->on('pt.kpi_id', '=', 'k.id')
                        ->where('pt.year', '=', $year);
                })
                ->select([
                    'c.id as commitment_id',
                    'c.name as commitment_name',
                    'd.id as deliverable_id',
                    'd.deliverable',
                    'k.id as kpi_id',
                    'k.kpi',
                    'k.unit_of_measurement',
                    'kt.target as target_value',
                    DB::raw('COALESCE(SUM(pt.actual_value), 0) as total_actual_value')
                ])
                ->where('s.id', '=', $sector->id)
                ->groupBy('c.id', 'c.name', 'd.id', 'd.deliverable', 'k.id', 'k.kpi', 'k.unit_of_measurement', 'kt.target')
                ->orderBy('c.id')
                ->orderBy('d.id')
                ->orderBy('k.id')
                ->get();

            $row = 5; // Start data from row 5 after headers
            $commitmentCounter = 1;
            $kpiCounter = 1; // Counter for KPI index
            $currentCommitmentId = null;
            $currentDeliverableId = null;
            $deliverableStartRow = null;
            $deliverableEndRow = null;

            // Arrays to track performance data for summaries
            $commitmentPerformanceData = [];
            $sectorPerformanceData = [];
            $currentCommitmentStartRow = null;

            // Track processed commitments to ensure all are handled
            $processedCommitments = [];

            foreach ($sectorData as $data) {
                // Check if this is a new commitment
                if ($data->commitment_id !== $currentCommitmentId) {
                    // Add commitment summary row for previous commitment if exists
                    if ($currentCommitmentId !== null && $currentCommitmentStartRow !== null) {
                        $commitmentEndRow = $row - 1;

                        // Add commitment average performance row
                        $sheet->setCellValue('C' . $row, 'Average Performance');
                        $sheet->getStyle('C' . $row)->getFont()->setBold(true);

                        // Add formula for average of column H values in this commitment
                        $hRange = 'H' . $currentCommitmentStartRow . ':H' . $commitmentEndRow;
                        $sheet->setCellValue('I' . $row, '=AVERAGE(' . $hRange . ')');
                        $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('0%');

                        // Track this row for the summary sheet using commitment ID for accuracy
                        $commitmentAverageRows[$sector->description][$currentCommitmentId] = $row;

                        // Make entire row bold
                        $sheet->getStyle('A' . $row . ':I' . $row)->getFont()->setBold(true);
                        $sheet->getStyle('A' . $row . ':I' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                        $row++;
                    }

                    // Merge the previous deliverable's A and B columns before starting new commitment
                    if ($currentDeliverableId !== null && $deliverableStartRow !== null && $deliverableEndRow !== null) {
                        if ($deliverableEndRow > $deliverableStartRow) {
                            $sheet->mergeCells('A' . $deliverableStartRow . ':A' . $deliverableEndRow);
                            $sheet->mergeCells('B' . $deliverableStartRow . ':B' . $deliverableEndRow);
                            $sheet->getStyle('A' . $deliverableStartRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                            $sheet->getStyle('B' . $deliverableStartRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                            $sheet->getStyle('B' . $deliverableStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        }
                    }

                    $currentCommitmentId = $data->commitment_id;
                    $currentCommitmentStartRow = $row + 1; // +1 because we're about to add commitment header
                    $processedCommitments[] = $data->commitment_id;

                    // Add commitment header row
                    $sheet->setCellValue('A' . $row, 'Commitment-' . $commitmentCounter . ' ' . $data->commitment_name);
                    $sheet->mergeCells('A' . $row . ':I' . $row);
                    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                    $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID);
                    $sheet->getStyle('A' . $row)->getFill()->getStartColor()->setRGB('E6F3FF');
                    $sheet->getStyle('A' . $row . ':I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle('A' . $row . ':I' . $row)->getAlignment()->setWrapText(true);
                    $sheet->getStyle('A' . $row . ':I' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getStyle('A' . $row . ':I' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                    $commitmentCounter++;
                    $row++;

                    // Reset deliverable tracking for new commitment
                    $currentDeliverableId = null;
                    $deliverableStartRow = null;
                    $deliverableEndRow = null;
                    $kpiCounter = 1; // Reset KPI counter for new commitment
                }

                // Check if this is a new deliverable
                if ($data->deliverable_id !== $currentDeliverableId) {
                    // Merge the previous deliverable's A and B columns
                    if ($currentDeliverableId !== null && $deliverableStartRow !== null && $deliverableEndRow !== null) {
                        if ($deliverableEndRow > $deliverableStartRow) {
                            $sheet->mergeCells('A' . $deliverableStartRow . ':A' . $deliverableEndRow);
                            $sheet->mergeCells('B' . $deliverableStartRow . ':B' . $deliverableEndRow);
                            $sheet->getStyle('A' . $deliverableStartRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                            $sheet->getStyle('B' . $deliverableStartRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                            $sheet->getStyle('B' . $deliverableStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        }
                    }

                    $currentDeliverableId = $data->deliverable_id;
                    $deliverableStartRow = $row;
                    $deliverableEndRow = $row;

                    // Set deliverable description in column B
                    $sheet->setCellValue('B' . $row, $data->deliverable);
                    $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true);
                } else {
                    // Same deliverable, update the end row
                    $deliverableEndRow = $row;
                }

                // Set KPI in column C
                $sheet->setCellValue('C' . $row, $data->kpi . ' (' . $data->unit_of_measurement . ')');

                // Leave column A empty
                $sheet->setCellValue('A' . $row, '');

                // Set KPI index in column D
                $sheet->setCellValue('D' . $row, $kpiCounter);

                // Set target value in column E
                $target = $data->target_value ?: 0;
                $sheet->setCellValue('E' . $row, $target);

                // Set actual value in column F (sum of actual_value from performance_trackings)
                $actual = $data->total_actual_value ?: 0;

                // Check if there are any performance tracking records for this KPI
                $hasPerformanceData = $data->total_actual_value !== null && $data->total_actual_value > 0;

                if ($hasPerformanceData) {
                    $sheet->setCellValue('F' . $row, $actual);

                    // Set performance formula in column G: F/E
                    $sheet->setCellValue('G' . $row, '=F' . $row . '/E' . $row);
                    $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE);

                    // Set adjusted performance formula in column H: =G
                    $sheet->setCellValue('H' . $row, '=G' . $row);
                    $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE);
                } else {
                    // No performance data available - use 0 for computation but display 'Not Assessed'
                    $sheet->setCellValue('F' . $row, 0);
                    $sheet->setCellValue('G' . $row, 0);
                    $sheet->setCellValue('H' . $row, 0);

                    // Set custom number format to display 'Not Assessed' for 0 values
                    $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('0%;"Not Assessed"');
                    $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('0%;"Not Assessed"');
                }

                // Leave column I empty
                $sheet->setCellValue('I' . $row, '');

                // Apply borders and text wrapping to the row
                $sheet->getStyle('A' . $row . ':I' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('A' . $row . ':I' . $row)->getAlignment()->setWrapText(true);
                $sheet->getStyle('A' . $row . ':I' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // Apply specific alignment for cells D-H (center horizontal, top vertical)
                $sheet->getStyle('D' . $row . ':H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D' . $row . ':H' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                $kpiCounter++; // Increment KPI counter
                $row++;
            }

            // Merge the last deliverable's A and B columns
            if ($currentDeliverableId !== null && $deliverableStartRow !== null && $deliverableEndRow !== null) {
                if ($deliverableEndRow > $deliverableStartRow) {
                    $sheet->mergeCells('A' . $deliverableStartRow . ':A' . $deliverableEndRow);
                    $sheet->mergeCells('B' . $deliverableStartRow . ':B' . $deliverableEndRow);
                    $sheet->getStyle('A' . $deliverableStartRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getStyle('B' . $deliverableStartRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getStyle('B' . $deliverableStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }
            }

            // Add commitment summary row for the last commitment
            if ($currentCommitmentId !== null && $currentCommitmentStartRow !== null) {
                $commitmentEndRow = $row - 1;

                // Add commitment average performance row
                $sheet->setCellValue('C' . $row, 'Average Performance');
                $sheet->getStyle('C' . $row)->getFont()->setBold(true);

                // Add formula for average of column H values in this commitment
                $hRange = 'H' . $currentCommitmentStartRow . ':H' . $commitmentEndRow;
                $sheet->setCellValue('I' . $row, '=AVERAGE(' . $hRange . ')');
                $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('0%');

                // Track this row for the summary sheet using commitment ID for accuracy
                $commitmentAverageRows[$sector->description][$currentCommitmentId] = $row;

                // Make entire row bold
                $sheet->getStyle('A' . $row . ':I' . $row)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row . ':I' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $row++;
            }

            // Handle commitments with no KPIs (not processed in the main loop)
            $allCommitments = DB::table('commitments')->where('sector_id', $sector->id)->orderBy('id')->get();
            foreach ($allCommitments as $commitment) {
                if (!in_array($commitment->id, $processedCommitments)) {
                    // Add commitment header row
                    $sheet->setCellValue('A' . $row, 'Commitment-' . $commitmentCounter . ' ' . $commitment->name);
                    $sheet->mergeCells('A' . $row . ':I' . $row);
                    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                    $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID);
                    $sheet->getStyle('A' . $row)->getFill()->getStartColor()->setRGB('E6F3FF');
                    $sheet->getStyle('A' . $row . ':I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle('A' . $row . ':I' . $row)->getAlignment()->setWrapText(true);
                    $sheet->getStyle('A' . $row . ':I' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getStyle('A' . $row . ':I' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $row++;

                    // Add placeholder row for no data
                    $sheet->setCellValue('C' . $row, 'No KPIs available');
                    $sheet->setCellValue('E' . $row, 0);
                    $sheet->setCellValue('F' . $row, 0);
                    $sheet->setCellValue('G' . $row, 0);
                    $sheet->setCellValue('H' . $row, 0);
                    $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('0%;"Not Assessed"');
                    $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('0%;"Not Assessed"');
                    $sheet->getStyle('A' . $row . ':I' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle('A' . $row . ':I' . $row)->getAlignment()->setWrapText(true);
                    $sheet->getStyle('A' . $row . ':I' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $row++;

                    // Add commitment average performance row
                    $sheet->setCellValue('C' . $row, 'Average Performance');
                    $sheet->getStyle('C' . $row)->getFont()->setBold(true);
                    $sheet->setCellValue('I' . $row, 0);
                    $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('0%;"Not Assessed"');

                    // Track this row for the summary sheet
                    $commitmentAverageRows[$sector->description][$commitment->id] = $row;

                    // Make entire row bold
                    $sheet->getStyle('A' . $row . ':I' . $row)->getFont()->setBold(true);
                    $sheet->getStyle('A' . $row . ':I' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $row++;

                    $commitmentCounter++;
                }
            }

            // Add sector overall summary row
            $sheet->setCellValue('C' . $row, 'Overall Average Performance');
            $sheet->getStyle('C' . $row)->getFont()->setBold(true);

            // Add formulas for overall averages
            $dataStartRow = 6; // Start after headers
            $dataEndRow = $row - 1; // End before this summary row

            // Formula for average of column G (Performance)
            $gRange = 'G' . $dataStartRow . ':G' . $dataEndRow;
            $sheet->setCellValue('G' . $row, '=AVERAGE(' . $gRange . ')');
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('0%');

            // Formula for average of column H (Adjusted Performance)
            $hRange = 'H' . $dataStartRow . ':H' . $dataEndRow;
            $sheet->setCellValue('H' . $row, '=AVERAGE(' . $hRange . ')');
            $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('0%');

            // Make entire row bold
            $sheet->getStyle('A' . $row . ':I' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':I' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // Track this overall average row for the grand summary sheet
            $sectorOverallAverageRows[$sector->description] = $row;

            // Add borders to the data range as well
            $lastRow = $row - 1;
            if ($lastRow > 4) {
                $sheet->getStyle('A5:I' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            }
        }

        return ['commitmentAverageRows' => $commitmentAverageRows, 'sectorOverallAverageRows' => $sectorOverallAverageRows]; // e06fc9d7
    }
}
