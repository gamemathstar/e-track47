<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprehensive KPI Tracking Report - Print</title>
    <style>
        @media print {
            .no-print {
                display: none;
            }

            .page-break {
                page-break-after: always;
                break-after: page;
            }

            @page {
                margin: 1cm;
                size: A4 landscape;
            }
        }

        body {
            font-family: 'Arial Narrow', Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            padding: 10px 20px;
            background-color: #008751;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .print-button:hover {
            background-color: #006d3f;
        }

        .sheet {
            margin-bottom: 50px;
            page-break-after: always;
        }

        .sheet:last-child {
            page-break-after: auto;
        }

        .sheet-title {
            font-family: 'Agency FB', Arial, sans-serif;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
            padding: 10px;
        }

        .sheet-subtitle {
            font-family: 'Arial Narrow', Arial, sans-serif;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
            padding: 8px;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 20px;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
            vertical-align: middle;
        }

        .report-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 10px;
        }

        .report-table td {
            background-color: #fff;
        }

        .merged-header {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .sector-header {
            background-color: #E6F3FF;
            font-weight: bold;
        }

        .commitment-header {
            background-color: #E6F3FF;
            font-weight: bold;
        }

        .summary-row {
            background-color: #F0F0F0;
            font-weight: bold;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .percentage {
            font-weight: bold;
        }
    </style>
</head>
<body>
<button class="print-button no-print" onclick="window.print()">Print</button>

<!-- Sheet 1: Overall Summary -->
<div class="sheet">
    <div class="sheet-title">Performance Delivery Coordination Unit (PDCU), Office of the Executive Governor</div>
    <div class="sheet-subtitle">{{ $startQuarterName }} to {{ $endQuarterName }} {{ $year }} MDA/Sector Summary of
        Performance on Commitments
    </div>

    <table class="report-table">
        <thead>
        <tr>
            <th rowspan="3">S/N</th>
            <th rowspan="3">Ministries and Agencies</th>
            <th rowspan="3">Commitments</th>
            <th rowspan="3">No. of Outputs</th>
            <th rowspan="3">No. of Results to be Delivered</th>
            <th colspan="6">Performance Tracking</th>
            <th rowspan="3">Overall Performance</th>
            <th rowspan="3">Rating</th>
        </tr>
        <tr>
            <th>Exceptional<br><small>Above 100%</small></th>
            <th>Above Expectation<br><small>70%-100%</small></th>
            <th>Meets Expectation<br><small>60%-69%</small></th>
            <th>Needs Improvement<br><small>40%-59%</small></th>
            <th>Below Minimum Expectation<br><small>Below 40%</small></th>
            <th>Not Assessed<br><small>N/A</small></th>
        </tr>
        </thead>
        <tbody>
        @foreach($overallSummaryData as $row)
            <tr>
                <td class="text-center">{{ $row['sn'] }}</td>
                <td style="text-align: left">{{ $row['sector_name'] }}</td>
                <td class="text-center">{{ $row['commitment_count'] ?: '-' }}</td>
                <td class="text-center">{{ $row['deliverable_count'] ?: '-' }}</td>
                <td class="text-center">{{ $row['kpi_count'] ?: '-' }}</td>
                <td class="text-center">{{ $row['exceptional_count'] > 0 ? $row['exceptional_count'] : '-' }}</td>
                <td class="text-center">{{ $row['above_expectation_count'] > 0 ? $row['above_expectation_count'] : '-' }}</td>
                <td class="text-center">{{ $row['meets_expectation_count'] > 0 ? $row['meets_expectation_count'] : '-' }}</td>
                <td class="text-center">{{ $row['needs_improvement_count'] > 0 ? $row['needs_improvement_count'] : '-' }}</td>
                <td class="text-center">{{ $row['below_minimum_count'] > 0 ? $row['below_minimum_count'] : '-' }}</td>
                <td class="text-center">{{ $row['not_assessed_count'] > 0 ? $row['not_assessed_count'] : '-' }}</td>
                <td class="text-center percentage">{{ $row['overall_performance'] > 0 ? number_format($row['overall_performance'], 2) . '%' : '-' }}</td>
                <td class="text-center">{{ $row['performance_rating'] ?: '-' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<!-- Sheet 2: Grand Summary -->
<div class="sheet">
    <div class="sheet-title">Performance Delivery Coordination Unit (PDCU), Office of the Executive Governor</div>
    <div class="sheet-subtitle">{{ $year }} Fiscal Year Snapshot View of MDA/Sector Performance ({{ $startQuarterName }}
        to {{ $endQuarterName }})
    </div>

    <table class="report-table">
        <thead>
        <tr>
            <th>S/N</th>
            <th>Sector/MDA</th>
            <th>Commitments</th>
            <th>No. of Outputs</th>
            <th>No. of Results to be Delivered</th>
            <th>Performance</th>
            <th>Rating</th>
        </tr>
        </thead>
        <tbody>
        @foreach($grandSummaryData as $row)
            <tr>
                <td class="text-center">{{ $row['sn'] }}</td>
                <td style="text-align: left">{{ $row['sector_name'] }}</td>
                <td class="text-center">{{ $row['commitment_count'] ?: '-' }}</td>
                <td class="text-center">{{ $row['deliverable_count'] ?: '-' }}</td>
                <td class="text-center">{{ $row['kpi_count'] ?: '-' }}</td>
                <td class="text-center percentage">{{ $row['average_performance'] > 0 ? number_format($row['average_performance'], 2) . '%' : '-' }}</td>
                <td class="text-center">{{ $row['performance_rating'] ?: '-' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<!-- Sheet 3: Sector Summary Details -->
<div class="sheet">
    <div class="sheet-title">Performance Delivery Coordination Unit (PDCU), Office of the Executive Governor</div>
    <div class="sheet-subtitle">{{ $startQuarterName }} to {{ $endQuarterName }} {{ $year }} MDA/Sector Summary of
        Performance on Commitments
    </div>

    <table class="report-table">
        <thead>
        <tr>
            <th rowspan="3">S/N</th>
            <th rowspan="3">Commitments</th>
            <th rowspan="3">No. of Outputs</th>
            <th rowspan="3">No of Results to be Delivered</th>
            <th colspan="6">Performance for Each Result</th>
            <th rowspan="2" colspan="2">Overall Performance</th>
        </tr>
        <tr>
            <th>Exceptional</th>
            <th>Above Expectation</th>
            <th>Meets Expectation</th>
            <th>Needs Improvement</th>
            <th>Below Minimum Expectation</th>
            <th>Not Assessed</th>
        </tr>
        <tr>
            <th>Above 100%</th>
            <th>70% - 100%</th>
            <th>60% - 69%</th>
            <th>40% - 59%</th>
            <th>Below 40%</th>
            <th>N/A</th>
            <th>Performance</th>
            <th>Rating</th>
        </tr>
        </thead>
        <tbody>
        @php
            $currentSector = null;
            $sn = 1;
        @endphp
        @foreach($sectorSummaryData as $row)
            @if($currentSector !== $row['sector_name'])
                @php
                    $currentSector = $row['sector_name'];
                @endphp
                <tr class="sector-header">
                    <td colspan="12" class="text-left"><strong>{{ $row['sector_name'] }}</strong></td>
                </tr>
            @endif
            <tr>
                <td class="text-center">{{ $sn++ }}</td>
                <td style="text-align: left">{{ $row['commitment_name'] }}</td>
                <td class="text-center">{{ $row['deliverable_count'] ?: '-' }}</td>
                <td class="text-center">{{ $row['kpi_count'] ?: '-' }}</td>
                <td class="text-center">{{ $row['exceptional_count'] > 0 ? $row['exceptional_count'] : '-' }}</td>
                <td class="text-center">{{ $row['above_expectation_count'] > 0 ? $row['above_expectation_count'] : '-' }}</td>
                <td class="text-center">{{ $row['meets_expectation_count'] > 0 ? $row['meets_expectation_count'] : '-' }}</td>
                <td class="text-center">{{ $row['needs_improvement_count'] > 0 ? $row['needs_improvement_count'] : '-' }}</td>
                <td class="text-center">{{ $row['below_minimum_count'] > 0 ? $row['below_minimum_count'] : '-' }}</td>
                <td class="text-center">{{ $row['not_assessed_count'] > 0 ? $row['not_assessed_count'] : '-' }}</td>
                <td class="text-center percentage">{{ $row['overall_performance'] > 0 ? number_format($row['overall_performance'], 2) . '%' : '-' }}</td>
                <td class="text-center">{{ $row['performance_rating'] ?: '-' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<!-- Individual Sector Sheets -->
@foreach($sectors as $sector)
    @if($sector->description && isset($individualSectorData[$sector->id]))
        <div class="sheet">
            <div class="sheet-title">Performance Delivery Coordination Unit (PDCU), Office of the Executive Governor
            </div>
            <div class="sheet-subtitle">PERFORMANCE ASSESSMENT [{{ $startQuarterName }}
                to {{ $endQuarterName }} {{ $year }}]
            </div>

            <table class="report-table">
                <thead>
                <tr>
                    <th rowspan="2">S/N</th>
                    {{--                    <th rowspan="2">Commitment</th>--}}
                    <th rowspan="2">Expected Outputs for Delivering the Outcome Targets</th>
                    <th rowspan="2">Output KPIs</th>
                    <th rowspan="2">Unit of Measurement</th>
                    <th rowspan="2">{{ $year }} Target</th>
                    <th rowspan="2">Jan. - Dec Results</th>
                    <th rowspan="2">Performance</th>
                    <th rowspan="2">Check</th>
                </tr>
                </thead>
                <tbody>
                @php
                    $currentCommitment = null;
                    $commitmentCounter = 1;
                    $kpiCounter = 1;
                @endphp
                @foreach($individualSectorData[$sector->id] as $data)
                    @if($currentCommitment !== $data->commitment_id)
                        @php
                            $currentCommitment = $data->commitment_id;
                            $kpiCounter = 1;
                        @endphp
                        <tr class="commitment-header">
                            <td colspan="8" style="text-align: left">
                                Commitment - {{ $commitmentCounter++ }}: {{ $data->commitment_name }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="text-center">{{ $kpiCounter++ }}</td>
                        {{--                        <td class="text-left"></td>--}}
                        <td style="text-align: left">{{ $data->deliverable }}</td>
                        <td class="text-left">{{ $data->kpi }}</td>
                        <td class="text-center">{{ $data->unit_of_measurement }}</td>
                        <td class="text-center">{{ $data->target_value ?: '-' }}</td>
                        <td class="text-center">{{ $data->total_actual_value ?: '-' }}</td>
                        <td class="text-center percentage">
                            @if($data->target_value > 0 && $data->total_actual_value > 0)
                                {{ number_format(($data->total_actual_value / $data->target_value) * 100, 2) }}%
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-center"></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endforeach

<script>
    // Auto-print when page loads
    window.onload = function () {
        setTimeout(function () {
            window.print();
        }, 500);
    };
</script>
</body>
</html>
