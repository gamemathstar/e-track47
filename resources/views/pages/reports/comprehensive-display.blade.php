@extends('layouts.app')

@section('css')
<style>
    .report-container {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin: 20px 0;
    }

    .nav-tabs {
        border-bottom: 2px solid #dee2e6;
        background: #f8f9fa;
        border-radius: 8px 8px 0 0;
    }

    .nav-tabs .nav-link {
        border: none;
        border-radius: 0;
        color: #495057;
        font-weight: 500;
        padding: 12px 20px;
        transition: all 0.3s ease;
    }

    .nav-tabs .nav-link:hover {
        background: #e9ecef;
        border: none;
        color: #495057;
    }

    .nav-tabs .nav-link.active {
        background: #008751;
        color: white;
        border: none;
        border-radius: 0;
    }

    .tab-content {
        padding: 20px;
    }

    .table-container {
        overflow-x: auto;
        overflow-y: auto;
        max-height: 600px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
    }

    .report-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        min-width: 800px;
    }

    .report-table th,
    .report-table td {
        border: 1px solid #dee2e6;
        padding: 8px;
        text-align: left;
        vertical-align: top;
        white-space: nowrap;
    }

    .report-table th {
        background-color: #f8f9fa;
        font-weight: bold;
        text-align: center;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .report-table th:first-child {
        position: sticky;
        left: 0;
        z-index: 20;
        background-color: #f8f9fa;
    }

    .report-table td:first-child {
        position: sticky;
        left: 0;
        z-index: 15;
        background-color: #fff;
    }

    .sector-header {
        background-color: #008751;
        color: white;
        font-weight: bold;
        text-align: center;
    }

    .commitment-header {
        background-color: #28a745;
        color: white;
        font-weight: bold;
        text-align: left;
    }

    .summary-row {
        background-color: #f8f9fa;
        font-weight: bold;
    }

    .performance-excellent {
        background-color: #d4edda;
        color: #155724;
    }

    .performance-good {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    .performance-fair {
        background-color: #fff3cd;
        color: #856404;
    }

    .performance-poor {
        background-color: #f8d7da;
        color: #721c24;
    }

    .performance-na {
        background-color: #e9ecef;
        color: #6c757d;
    }

    .export-section {
        margin: 20px 0;
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 5px;
    }

    .year-selector {
        margin-bottom: 20px;
    }

    .tab-pane {
        display: none;
    }

    .tab-pane.active {
        display: block;
    }

    .sheet-title {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 15px;
        color: #333;
        text-align: center;
    }

    .performance-cell {
        text-align: center;
        font-weight: 500;
    }

    .sticky-header {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #f8f9fa;
    }

    .sticky-first-column {
        position: sticky;
        left: 0;
        z-index: 15;
        background-color: #fff;
    }

    .sticky-first-column-header {
        position: sticky;
        left: 0;
        z-index: 25;
        background-color: #f8f9fa;
    }
</style>
@endsection

@section('content')
<div class="content">
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Comprehensive KPI Tracking Report - Browser View
        </h2>
        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <a href="{{ route('reports.comprehensive') }}" class="btn btn-secondary mr-2">
                <i class="w-4 h-4 mr-2" data-lucide="arrow-left"></i>
                Back to Reports
            </a>
        </div>
    </div>

    <div class="export-section">
        <div class="year-selector">
            <form method="GET" action="{{ route('reports.comprehensive.display') }}" class="flex items-center">
                <label for="year" class="mr-2 font-medium">Select Year:</label>
                <select name="year" id="year" class="form-select w-32 mr-4" onchange="this.form.submit()">
                    @for($y = date('Y'); $y >= 2020; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>
            </form>
        </div>

        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('reports.comprehensive.download') }}" class="inline">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <button type="submit" class="btn btn-primary">
                    <i class="w-4 h-4 mr-2" data-lucide="download"></i>
                    Export to Excel
                </button>
            </form>
        </div>
    </div>

    <div class="mt-6">
        <div class="alert alert-info">
            <i class="w-4 h-4 mr-2" data-lucide="info"></i>
            <strong>Report Structure:</strong> This report displays the comprehensive KPI tracking data organized by Excel sheets. Use the tabs below to navigate between different sections of the report.
        </div>
    </div>

    <div class="report-container">
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs" id="reportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overall-summary-tab" data-bs-toggle="tab" data-bs-target="#overall-summary" type="button" role="tab">
                    Overall Summary
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="grand-summary-tab" data-bs-toggle="tab" data-bs-target="#grand-summary" type="button" role="tab">
                    Grand Summary
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sector-summary-tab" data-bs-toggle="tab" data-bs-target="#sector-summary" type="button" role="tab">
                    Sector Summary Details
                </button>
            </li>
            @foreach($sectors as $sector)
                @if($sector->description)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="sector-{{ $sector->id }}-tab" data-bs-toggle="tab" data-bs-target="#sector-{{ $sector->id }}" type="button" role="tab">
                            {{ $sector->sector_name }}
                        </button>
                    </li>
                @endif
            @endforeach
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="reportTabsContent">
            <!-- Overall Summary Tab -->
            <div class="tab-pane fade show active" id="overall-summary" role="tabpanel">
                <div class="sheet-title">Performance Delivery Coordination Unit (PDCU), Office of the Executive Governor</div>
                <div class="sheet-title">January to December {{ $year }} MDA/Sector Summary of Performance on Commitments</div>
                
                <div class="table-container">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th class="sticky-first-column-header">S/N</th>
                                <th>Ministries and Agencies</th>
                                <th>Commitments</th>
                                <th>No. of Outputs</th>
                                <th>No. of Results to be Delivered</th>
                                <th>Exceptional<br><small>Above 100%</small></th>
                                <th>Above Expectation<br><small>70%-100%</small></th>
                                <th>Meets Expectation<br><small>60%-69%</small></th>
                                <th>Needs Improvement<br><small>40%-59%</small></th>
                                <th>Below Minimum Expectation<br><small>Below 40%</small></th>
                                <th>Not Assessed<br><small>N/A</small></th>
                                <th>Performance</th>
                                <th>Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($overallSummaryData as $index => $data)
                                <tr>
                                    <td class="sticky-first-column">{{ $index + 1 }}</td>
                                    <td>{{ $data['sector_name'] }}</td>
                                    <td class="text-center">{{ $data['commitment_count'] }}</td>
                                    <td class="text-center">{{ $data['deliverable_count'] }}</td>
                                    <td class="text-center">{{ $data['kpi_count'] }}</td>
                                    <td class="text-center">{{ $data['exceptional_count'] ?? '-' }}</td>
                                    <td class="text-center">{{ $data['above_expectation_count'] ?? '-' }}</td>
                                    <td class="text-center">{{ $data['meets_expectation_count'] ?? '-' }}</td>
                                    <td class="text-center">{{ $data['needs_improvement_count'] ?? '-' }}</td>
                                    <td class="text-center">{{ $data['below_minimum_count'] ?? '-' }}</td>
                                    <td class="text-center">{{ $data['not_assessed_count'] ?? '-' }}</td>
                                    <td class="text-center performance-cell">{{ number_format($data['average_performance'], 2) }}%</td>
                                    <td class="text-center">{{ $data['performance_rating'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Grand Summary Tab -->
            <div class="tab-pane fade" id="grand-summary" role="tabpanel">
                <div class="sheet-title">Performance Delivery Coordination Unit (PDCU), Office of the Executive Governor</div>
                <div class="sheet-title">{{ $year }} Fiscal Year Snapshot View of MDA/Sector Performance (January to December)</div>
                
                <div class="table-container">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th class="sticky-first-column-header">S/N</th>
                                <th>Names of MDAs/Sectors</th>
                                <th>No. of Commitments</th>
                                <th>No. of Outputs</th>
                                <th>No of Results to be Delivered</th>
                                <th>Performance at Mid-Year</th>
                                <th>Fully Performance</th>
                                <th>Fully Performance Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grandSummaryData as $index => $data)
                                <tr>
                                    <td class="sticky-first-column">{{ $index + 1 }}</td>
                                    <td>{{ $data['sector_name'] }}</td>
                                    <td class="text-center">{{ $data['commitment_count'] }}</td>
                                    <td class="text-center">{{ $data['deliverable_count'] }}</td>
                                    <td class="text-center">{{ $data['kpi_count'] }}</td>
                                    <td class="text-center">-</td>
                                    <td class="text-center performance-cell">{{ number_format($data['average_performance'], 2) }}%</td>
                                    <td class="text-center">{{ $data['performance_rating'] }}</td>
                                </tr>
                            @endforeach
                            <tr class="summary-row">
                                <td class="sticky-first-column">{{ count($grandSummaryData) + 1 }}</td>
                                <td>Overall State Performance</td>
                                <td class="text-center">{{ array_sum(array_column($grandSummaryData, 'commitment_count')) }}</td>
                                <td class="text-center">{{ array_sum(array_column($grandSummaryData, 'deliverable_count')) }}</td>
                                <td class="text-center">{{ array_sum(array_column($grandSummaryData, 'kpi_count')) }}</td>
                                <td class="text-center">-</td>
                                <td class="text-center performance-cell">{{ number_format(array_sum(array_column($grandSummaryData, 'average_performance')) / count($grandSummaryData), 2) }}%</td>
                                <td class="text-center">-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sector Summary Details Tab -->
            <div class="tab-pane fade" id="sector-summary" role="tabpanel">
                <div class="sheet-title">Performance Delivery Coordination Unit (PDCU), Office of the Executive Governor</div>
                <div class="sheet-title">January to December {{ $year }} MDA/Sector Summary of Performance on Commitments</div>
                
                <div class="table-container">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th class="sticky-first-column-header">S/N</th>
                                <th>Commitments</th>
                                <th>No. of Outputs</th>
                                <th>No of Results to be Delivered</th>
                                <th>Exceptional<br><small>Above 100%</small></th>
                                <th>Above Expectation<br><small>70% - 100%</small></th>
                                <th>Meets Expectation<br><small>60% - 69%</small></th>
                                <th>Needs Improvement<br><small>40% - 59%</small></th>
                                <th>Below Minimum Expectation<br><small>Below 40%</small></th>
                                <th>Not Assessed<br><small>N/A</small></th>
                                <th>Performance</th>
                                <th>Rating</th>
                                <th>r</th>
                                <th>Check</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sectorSummaryData as $index => $data)
                                <tr class="sector-header">
                                    <td class="sticky-first-column"></td>
                                    <td colspan="13">{{ $data['sector_name'] }}</td>
                                </tr>
                                <tr>
                                    <td class="sticky-first-column">{{ $index + 1 }}</td>
                                    <td>{{ $data['sector_name'] }}</td>
                                    <td class="text-center">{{ $data['output_count'] }}</td>
                                    <td class="text-center">{{ $data['result_count'] }}</td>
                                    <td class="text-center">{{ $data['exceptional_count'] ?? '-' }}</td>
                                    <td class="text-center">{{ $data['above_expectation_count'] ?? '-' }}</td>
                                    <td class="text-center">{{ $data['meets_expectation_count'] ?? '-' }}</td>
                                    <td class="text-center">{{ $data['needs_improvement_count'] ?? '-' }}</td>
                                    <td class="text-center">{{ $data['below_minimum_count'] ?? '-' }}</td>
                                    <td class="text-center">{{ $data['not_assessed_count'] ?? '-' }}</td>
                                    <td class="text-center performance-cell">{{ number_format($data['overall_performance'], 2) }}%</td>
                                    <td class="text-center">{{ $data['performance_rating'] }}</td>
                                    <td class="text-center">-</td>
                                    <td class="text-center">-</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Individual Sector Tabs -->
            @foreach($sectors as $sector)
                <div class="tab-pane fade" id="sector-{{ $sector->id }}" role="tabpanel">
                    <div class="sheet-title">{{ strtoupper($sector->sector_name) }}</div>
                    <div class="sheet-title">FULL YEAR [JANUARY TO DECEMBER {{ $year }}] PERFORMANCE ASSESSMENT</div>
                    
                    <div class="table-container">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th class="sticky-first-column-header">No. of Ops</th>
                                    <th>Expected Outputs for Delivering the Outcome Targets</th>
                                    <th>Output KPIs</th>
                                    <th>Results No.</th>
                                    <th>{{ $year }} Target</th>
                                    <th>Jan. - Dec Results</th>
                                    <th>Performance</th>
                                    <th>Adjusted Performance</th>
                                    <th>Evidences</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($individualSectorData[$sector->description]))
                                    @foreach($individualSectorData[$sector->description] as $data)
                                        @if($data['type'] === 'commitment_header')
                                            <tr class="commitment-header">
                                                <td class="sticky-first-column" colspan="9">{{ $data['commitment_name'] }}</td>
                                            </tr>
                                        @else
                                            <tr>
                                                <td class="sticky-first-column"></td>
                                                <td>{{ $data['deliverable'] }}</td>
                                                <td>{{ $data['kpi'] }}</td>
                                                <td class="text-center">{{ $data['kpi_counter'] }}</td>
                                                <td class="text-center">{{ $data['target'] }}</td>
                                                <td class="text-center">{{ $data['actual'] }}</td>
                                                                                                 <td class="text-center performance-cell {{ $data['has_performance_data'] ? 'performance-' . $data['performance_class'] : 'performance-na' }}">
                                                     {{ $data['has_performance_data'] ? number_format($data['performance'], 2) . '%' : 'Not Assessed' }}
                                                 </td>
                                                 <td class="text-center performance-cell {{ $data['has_performance_data'] ? 'performance-' . $data['performance_class'] : 'performance-na' }}">
                                                     {{ $data['has_performance_data'] ? number_format($data['adjusted_performance'], 2) . '%' : 'Not Assessed' }}
                                                 </td>
                                                <td class="text-center">-</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="sticky-first-column" colspan="9" class="text-center">No data available for this sector</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tabs
    var triggerTabList = [].slice.call(document.querySelectorAll('#reportTabs button'))
    triggerTabList.forEach(function (triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl)
        
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault()
            tabTrigger.show()
        })
    })

    // Add smooth scrolling to table containers
    const tableContainers = document.querySelectorAll('.table-container');
    tableContainers.forEach(container => {
        container.addEventListener('scroll', function() {
            // Keep sticky headers and first column in sync
            const table = container.querySelector('.report-table');
            if (table) {
                const stickyHeaders = table.querySelectorAll('.sticky-header, .sticky-first-column-header');
                const stickyColumns = table.querySelectorAll('.sticky-first-column');
                
                stickyHeaders.forEach(header => {
                    header.style.transform = `translateY(${container.scrollTop}px)`;
                });
                
                stickyColumns.forEach(column => {
                    column.style.transform = `translateX(${container.scrollLeft}px)`;
                });
            }
        });
    });
});
</script>
@endsection
