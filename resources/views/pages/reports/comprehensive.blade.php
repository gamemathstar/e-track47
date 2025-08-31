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

        <div class="intro-y grid grid-cols-12 gap-5 mt-5">
            <div class="col-span-12 lg:col-span-12 2xl:col-span-12">
                <div class="box p-5 rounded-md">
                    <div class="flex items-center border-slate-200/60 dark:border-darkmode-400">
                        <div class="text-primary text-2xl">Export Comprehensive Report</div>
                    </div>
                    <form method="POST" action="{{ route('reports.comprehensive.download') }}">
                        @csrf
                        <div class="grid grid-cols-12 gap-4 gap-y-3 mt-3">
                            <div class="col-span-3 sm:col-span-3">
                                <label for="start_month" class="form-label">Start Month</label>
                                <select name="start_month" id="start_month" class="form-control">
                                    @for ($i = 1; $i <= 12; $i++)
                                        <option
                                            value="{{ $i }}" {{ (request('start_month', 1) == $i) ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-span-3 sm:col-span-3">
                                <label for="end_month" class="form-label">End Month</label>
                                <select name="end_month" id="end_month" class="form-control">
                                    @for ($i = 1; $i <= 12; $i++)
                                        <option
                                            value="{{ $i }}" {{ (request('end_month', 12) == $i) ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-span-3 sm:col-span-3">
                                <label for="year" class="form-label">Year</label>
                                <select name="year" id="year" class="form-control">
                                    @for($y = date('Y'); $y >= 2020; $y--)
                                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                            {{ $y }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-span-3 sm:col-span-3 mt-5">
                                <button type="submit" class="btn btn-primary w-52">
                                    <i class="w-4 h-4 mr-2" data-lucide="download"></i>
                                    Export to Excel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        {{--    <div class="mt-6">--}}
        {{--        <div class="alert alert-info">--}}
        {{--            <i class="w-4 h-4 mr-2" data-lucide="info"></i>--}}
        {{--            <strong>Report Structure:</strong> This report shows the comprehensive KPI tracking data organized by Sector → Commitment → Deliverable → KPI, matching the format of the official Excel template.--}}
        {{--        </div>--}}
        {{--    </div>--}}

        {{--    <div class="mt-6">--}}
        {{--        <table class="comprehensive-table">--}}
        {{--            <thead>--}}
        {{--                <tr>--}}
        {{--                    <th>No. of Ops</th>--}}
        {{--                    <th>Expected Outputs for Delivering the Outcome Targets</th>--}}
        {{--                    <th>Output KPIs</th>--}}
        {{--                    <th>Results No.</th>--}}
        {{--                    <th>{{ $year }} Target</th>--}}
        {{--                    <th>Jan. - Dec Results</th>--}}
        {{--                    <th>Performance</th>--}}
        {{--                    <th>Adjusted Performance</th>--}}
        {{--                    <th>Evidences</th>--}}
        {{--                    <th>Notes</th>--}}
        {{--                </tr>--}}
        {{--            </thead>--}}
        {{--            <tbody>--}}
        {{--                @foreach($reportData as $data)--}}
        {{--                    @if(empty($data['operation_number']) && !empty($data['deliverable_description']))--}}
        {{--                        @if(str_contains($data['deliverable_description'], 'Ministry') || str_contains($data['deliverable_description'], 'Office') || str_contains($data['deliverable_description'], 'Department'))--}}
        {{--                            <tr class="sector-header">--}}
        {{--                                <td colspan="10">{{ $data['deliverable_description'] }}</td>--}}
        {{--                            </tr>--}}
        {{--                        @else--}}
        {{--                            <tr class="commitment-header">--}}
        {{--                                <td colspan="10">{{ $data['deliverable_description'] }}</td>--}}
        {{--                            </tr>--}}
        {{--                        @endif--}}
        {{--                    @else--}}
        {{--                        <tr class="kpi-row">--}}
        {{--                            <td>{{ $data['operation_number'] }}</td>--}}
        {{--                            <td>{{ $data['deliverable_description'] }}</td>--}}
        {{--                            <td>{{ $data['kpi_description'] }}</td>--}}
        {{--                            <td>{{ $data['result_number'] }}</td>--}}
        {{--                            <td>{{ $data['target_value'] }}</td>--}}
        {{--                            <td>{{ $data['actual_result'] }}</td>--}}
        {{--                            <td class="{{ $data['performance_ratio'] !== 'NA' ? 'performance-cell' : '' }}">--}}
        {{--                                {{ $data['performance_ratio'] }}--}}
        {{--                            </td>--}}
        {{--                            <td class="{{ $data['adjusted_performance'] !== 'NA' ? 'performance-cell' : '' }}">--}}
        {{--                                {{ $data['adjusted_performance'] }}--}}
        {{--                            </td>--}}
        {{--                            <td>{{ $data['evidence'] }}</td>--}}
        {{--                            <td>{{ $data['notes'] }}</td>--}}
        {{--                        </tr>--}}
        {{--                    @endif--}}
        {{--                @endforeach--}}
        {{--            </tbody>--}}
        {{--        </table>--}}
        {{--    </div>--}}
    </div>
@endsection

@section('js')
    <script>
        // Ensure End Month is always greater than or equal to Start Month
        document.getElementById('start_month').addEventListener('change', function () {
            const startMonth = parseInt(this.value);
            const endMonthSelect = document.getElementById('end_month');
            const endMonth = parseInt(endMonthSelect.value);

            if (endMonth < startMonth) {
                endMonthSelect.value = startMonth;
            }
        });

        document.getElementById('end_month').addEventListener('change', function () {
            const endMonth = parseInt(this.value);
            const startMonthSelect = document.getElementById('start_month');
            const startMonth = parseInt(startMonthSelect.value);

            if (startMonth > endMonth) {
                startMonthSelect.value = endMonth;
            }
        });

        // Set default values for year if not already set
        document.addEventListener('DOMContentLoaded', function () {
            const yearSelect = document.getElementById('year');
            if (!yearSelect.value) {
                yearSelect.value = new Date().getFullYear();
            }
        });
    </script>
@endsection
