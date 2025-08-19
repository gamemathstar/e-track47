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

        .quarter-header {
            background-color: #e0e0e0;
            text-align: center;
            font-weight: bold;
        }

        .quarter-group {
            background-color: #f8f9fa;
        }

        .sector-header {
            background-color: #d4edda;
            font-weight: bold;
            text-align: center;
        }

        .commitment-header {
            background-color: #fff3cd;
            font-weight: bold;
        }

        .deliverable-header {
            background-color: #f8d7da;
            font-weight: bold;
        }

        .kpi-row {
            background-color: #ffffff;
        }

        .quarter-cell {
            text-align: center;
            min-width: 80px;
        }

        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-not-confirmed {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .export-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .year-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
    </style>
@endsection

@section('content')
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Comprehensive KPI Tracking Report
        </h2>
        <div class="w-full sm:w-auto flex mt-4 sm:mt-0"></div>
    </div>

    <!-- Export Section -->
    <div class="export-section">
        <div class="year-selector">
            <label for="year" class="form-label font-bold">Select Year:</label>
            <select name="year" id="year" class="form-control w-32" onchange="changeYear(this.value)">
                @for ($i = date('Y'); $i >= 2020; $i--)
                    <option value="{{ $i }}" {{ $i == $year ? 'selected' : '' }}>
                        {{ $i }}
                    </option>
                @endfor
            </select>

            <form action="{{ route('reports.comprehensive.download') }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <button type="submit" class="btn btn-primary">
                    <i class="w-4 h-4 mr-2" data-lucide="download"></i>
                    Export to Excel
                </button>
            </form>
        </div>

        <div class="text-sm text-gray-600">
            <strong>Report Summary:</strong>
            Showing {{ count($reportData) }} KPI entries across all sectors for {{ $year }}
        </div>
    </div>

    <!-- Table Container -->
    <div class="table-container">
        <table class="comprehensive-table">
            <thead>
                <tr>
                    <th rowspan="3">S/N</th>
                    <th rowspan="3">Sector/MDA</th>
                    <th rowspan="3">Commitment</th>
                    <th rowspan="3">Deliverable</th>
                    <th rowspan="3">KPI</th>
                    <th rowspan="3">Unit of Measurement</th>
                    <th rowspan="3">Target Value</th>
                    <th colspan="5" class="quarter-header">Q1</th>
                    <th colspan="5" class="quarter-header">Q2</th>
                    <th colspan="5" class="quarter-header">Q3</th>
                    <th colspan="5" class="quarter-header">Q4</th>
                </tr>
                <tr>
                    <th colspan="5" class="quarter-header">Q1 Performance</th>
                    <th colspan="5" class="quarter-header">Q2 Performance</th>
                    <th colspan="5" class="quarter-header">Q3 Performance</th>
                    <th colspan="5" class="quarter-header">Q4 Performance</th>
                </tr>
                <tr>
                    <!-- Q1 Headers -->
                    <th class="quarter-cell">Milestone</th>
                    <th class="quarter-cell">Actual</th>
                    <th class="quarter-cell">Remarks</th>
                    <th class="quarter-cell">Status</th>
                    <th class="quarter-cell">Date</th>

                    <!-- Q2 Headers -->
                    <th class="quarter-cell">Milestone</th>
                    <th class="quarter-cell">Actual</th>
                    <th class="quarter-cell">Remarks</th>
                    <th class="quarter-cell">Status</th>
                    <th class="quarter-cell">Date</th>

                    <!-- Q3 Headers -->
                    <th class="quarter-cell">Milestone</th>
                    <th class="quarter-cell">Actual</th>
                    <th class="quarter-cell">Remarks</th>
                    <th class="quarter-cell">Status</th>
                    <th class="quarter-cell">Date</th>

                    <!-- Q4 Headers -->
                    <th class="quarter-cell">Milestone</th>
                    <th class="quarter-cell">Actual</th>
                    <th class="quarter-cell">Remarks</th>
                    <th class="quarter-cell">Status</th>
                    <th class="quarter-cell">Date</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $currentSector = '';
                    $currentCommitment = '';
                    $currentDeliverable = '';
                @endphp

                @foreach ($reportData as $data)
                    @if ($currentSector != $data['sector_name'])
                        @php $currentSector = $data['sector_name']; @endphp
                        <tr class="sector-header">
                            <td colspan="27">{{ $data['sector_name'] }}</td>
                        </tr>
                    @endif

                    @if ($currentCommitment != $data['commitment_name'])
                        @php $currentCommitment = $data['commitment_name']; @endphp
                        <tr class="commitment-header">
                            <td colspan="27">{{ $data['commitment_name'] }}</td>
                        </tr>
                    @endif

                    @if ($currentDeliverable != $data['deliverable'])
                        @php $currentDeliverable = $data['deliverable']; @endphp
                        <tr class="deliverable-header">
                            <td colspan="27">{{ $data['deliverable'] }}</td>
                        </tr>
                    @endif

                    <tr class="kpi-row">
                        <td>{{ $data['s_n'] }}</td>
                        <td>{{ $data['sector_name'] }}</td>
                        <td>{{ $data['commitment_name'] }}</td>
                        <td>{{ $data['deliverable'] }}</td>
                        <td>{{ $data['kpi'] }}</td>
                        <td>{{ $data['unit_of_measurement'] }}</td>
                        <td>{{ $data['target_value'] }}</td>

                        <!-- Q1 Data -->
                        <td class="quarter-cell">{{ $data['q1_milestone'] ?: '-' }}</td>
                        <td class="quarter-cell">{{ $data['q1_actual'] ?: '-' }}</td>
                        <td class="quarter-cell">{{ Str::limit($data['q1_remarks'], 30) ?: '-' }}</td>
                        <td class="quarter-cell">
                            @if($data['q1_status'])
                                <span class="status-{{ strtolower(str_replace(' ', '-', $data['q1_status'])) }}">
                                    {{ $data['q1_status'] }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="quarter-cell">{{ $data['q1_date'] ? date('M d', strtotime($data['q1_date'])) : '-' }}</td>

                        <!-- Q2 Data -->
                        <td class="quarter-cell">{{ $data['q2_milestone'] ?: '-' }}</td>
                        <td class="quarter-cell">{{ $data['q2_actual'] ?: '-' }}</td>
                        <td class="quarter-cell">{{ Str::limit($data['q2_remarks'], 30) ?: '-' }}</td>
                        <td class="quarter-cell">
                            @if($data['q2_status'])
                                <span class="status-{{ strtolower(str_replace(' ', '-', $data['q2_status'])) }}">
                                    {{ $data['q2_status'] }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="quarter-cell">{{ $data['q2_date'] ? date('M d', strtotime($data['q2_date'])) : '-' }}</td>

                        <!-- Q3 Data -->
                        <td class="quarter-cell">{{ $data['q3_milestone'] ?: '-' }}</td>
                        <td class="quarter-cell">{{ $data['q3_actual'] ?: '-' }}</td>
                        <td class="quarter-cell">{{ Str::limit($data['q3_remarks'], 30) ?: '-' }}</td>
                        <td class="quarter-cell">
                            @if($data['q3_status'])
                                <span class="status-{{ strtolower(str_replace(' ', '-', $data['q3_status'])) }}">
                                    {{ $data['q3_status'] }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="quarter-cell">{{ $data['q3_date'] ? date('M d', strtotime($data['q3_date'])) : '-' }}</td>

                        <!-- Q4 Data -->
                        <td class="quarter-cell">{{ $data['q4_milestone'] ?: '-' }}</td>
                        <td class="quarter-cell">{{ $data['q4_actual'] ?: '-' }}</td>
                        <td class="quarter-cell">{{ Str::limit($data['q4_remarks'], 30) ?: '-' }}</td>
                        <td class="quarter-cell">
                            @if($data['q4_status'])
                                <span class="status-{{ strtolower(str_replace(' ', '-', $data['q4_status'])) }}">
                                    {{ $data['q4_status'] }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="quarter-cell">{{ $data['q4_date'] ? date('M d', strtotime($data['q4_date'])) : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if(empty($reportData))
        <div class="mt-8 text-center text-gray-500">
            <i class="w-16 h-16 mx-auto mb-4" data-lucide="file-text"></i>
            <h3 class="text-lg font-medium">No Data Available</h3>
            <p class="text-sm">No KPI tracking data found for the selected year.</p>
        </div>
    @endif
@endsection

@section('js')
    <script>
        function changeYear(year) {
            window.location.href = '{{ route("reports.comprehensive") }}?year=' + year;
        }

        // Add tooltips for truncated remarks
        document.addEventListener('DOMContentLoaded', function() {
            const remarkCells = document.querySelectorAll('td:nth-child(13), td:nth-child(18), td:nth-child(23), td:nth-child(28)');
            remarkCells.forEach(cell => {
                if (cell.textContent.trim() !== '-') {
                    cell.title = cell.textContent.trim();
                }
            });
        });
    </script>
@endsection
