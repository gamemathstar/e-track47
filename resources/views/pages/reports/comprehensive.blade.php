@extends('layouts.app')

@section('css')
    <style>
        .comprehensive-table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
            font-size: 12px;
        }

        .comprehensive-table th,
        .comprehensive-table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }

        .comprehensive-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        .sector-header {
            background-color: #008751;
            color: white;
            font-weight: bold;
            text-align: center;
        }

        .commitment-header {
            background-color: #f39c12;
            color: white;
            font-weight: bold;
            text-align: left;
        }

        .kpi-row {
            background-color: #ffffff;
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

        .export-section {
            margin: 20px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .year-selector {
            margin-bottom: 20px;
        }
    </style>
@endsection

@section('content')
<div class="content">
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Comprehensive KPI Tracking Report
        </h2>
        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <a href="{{ route('reports.index') }}" class="btn btn-secondary mr-2">
                <i class="w-4 h-4 mr-2" data-lucide="arrow-left"></i>
                Back to Reports
            </a>
        </div>
    </div>

    <div class="export-section">
        <div class="year-selector">
            <form method="GET" action="{{ route('reports.comprehensive') }}" class="flex items-center">
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
            <form method="POST" action="{{ route('reports.comprehensive.download') }}">
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
            <strong>Report Structure:</strong> This report shows the comprehensive KPI tracking data organized by Sector → Commitment → Deliverable → KPI, matching the format of the official Excel template.
        </div>
    </div>

    <div class="mt-6">
        <table class="comprehensive-table">
            <thead>
                <tr>
                    <th>No. of Ops</th>
                    <th>Expected Outputs for Delivering the Outcome Targets</th>
                    <th>Output KPIs</th>
                    <th>Results No.</th>
                    <th>{{ $year }} Target</th>
                    <th>Jan. - Dec Results</th>
                    <th>Performance</th>
                    <th>Adjusted Performance</th>
                    <th>Evidences</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $data)
                    @if(empty($data['operation_number']) && !empty($data['deliverable_description']))
                        @if(str_contains($data['deliverable_description'], 'Ministry') || str_contains($data['deliverable_description'], 'Office') || str_contains($data['deliverable_description'], 'Department'))
                            <tr class="sector-header">
                                <td colspan="10">{{ $data['deliverable_description'] }}</td>
                            </tr>
                        @else
                            <tr class="commitment-header">
                                <td colspan="10">{{ $data['deliverable_description'] }}</td>
                            </tr>
                        @endif
                    @else
                        <tr class="kpi-row">
                            <td>{{ $data['operation_number'] }}</td>
                            <td>{{ $data['deliverable_description'] }}</td>
                            <td>{{ $data['kpi_description'] }}</td>
                            <td>{{ $data['result_number'] }}</td>
                            <td>{{ $data['target_value'] }}</td>
                            <td>{{ $data['actual_result'] }}</td>
                            <td class="{{ $data['performance_ratio'] !== 'NA' ? 'performance-cell' : '' }}">
                                {{ $data['performance_ratio'] }}
                            </td>
                            <td class="{{ $data['adjusted_performance'] !== 'NA' ? 'performance-cell' : '' }}">
                                {{ $data['adjusted_performance'] }}
                            </td>
                            <td>{{ $data['evidence'] }}</td>
                            <td>{{ $data['notes'] }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('js')
<script>
    // Add any JavaScript functionality here if needed
</script>
@endsection
