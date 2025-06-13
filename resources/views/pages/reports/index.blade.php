@php use Carbon\Carbon; @endphp
@extends('layouts.app')

@section('css')
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .merged {
            background-color: #e0e0e0;
            text-align: center;
        }
    </style>
@endsection

@section('content')
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Reports
        </h2>
        <div class="w-full sm:w-auto flex mt-4 sm:mt-0"></div>
    </div>
    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12 lg:col-span-12 2xl:col-span-12">
            <div class="box p-5 rounded-md">
                <div class="flex items-center border-slate-200/60 dark:border-darkmode-400">
                    <div class="text-primary text-2xl">Generate Report</div>
                </div>
                <form action="{{ route('reports.generate') }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-12 gap-4 gap-y-3 mt-3">
                        <div class="col-span-3 sm:col-span-3">
                            <label for="start_month" class="form-label">Start Month</label>
                            <select name="start_month" id="start_month" class="form-control">
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}">{{ date('F', mktime(0, 0, 0, $i, 1)) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-span-3 sm:col-span-3">
                            <label for="end_month" class="form-label">End Month</label>
                            <select name="end_month" id="end_month" class="form-control">
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}">{{ date('F', mktime(0, 0, 0, $i, 1)) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-span-3 sm:col-span-3">
                            <label for="modal-form-2" class="form-label">Description</label>
                            <input type="number" name="year" id="year" value="{{ date('Y') }}" class="form-control"
                                   min="2020" max="2030">
                        </div>
                        <div class="col-span-3 sm:col-span-3 mt-5">
                            <input type="submit" class="btn btn-primary w-52" value="Generate">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if(isset($snapshotData) && isset($summaryData))
        <div class="intro-y grid grid-cols-12 gap-5 mt-5">
            <div class="col-span-12 lg:col-span-12 2xl:col-span-12">
                <div class="box pt-5 pl-5 pr-5 rounded-md">
                    <div class="flex items-center border-slate-200/60 dark:border-darkmode-400">
                        <div class="text-primary text-2xl">Generated Report</div>
                    </div>
                    <ul class="nav nav-link-tabs flex-col sm:flex-row justify-center lg:justify-start text-center"
                        role="tablist">
                        <li id="profile-tab" class="nav-item" role="presentation">
                            <a href="javascript:;" class="nav-link py-4 flex items-center active"
                               data-tw-target="#profile"
                               aria-controls="profile" aria-selected="true" role="tab">
                                <i class="w-4 h-4 mr-2" data-lucide="bar-chart-2"></i>
                                Overall Grand Summary
                            </a>
                        </li>
                        <li id="change-photo-tab" class="nav-item" role="presentation">
                            <a href="javascript:;" class="nav-link py-4 flex items-center"
                               data-tw-target="#change-photo" aria-selected="false" role="tab">
                                <i class="w-4 h-4 mr-2" data-lucide="bar-chart-2"></i>
                                MDA Sector Summary
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>


        <div class="tab-content mt-5">
            <div id="profile" class="tab-pane active" role="tabpanel" aria-labelledby="profile-tab">
                <div class="grid grid-cols-12 gap-6">
                    <!-- BEGIN: Latest Uploads -->
                    <div class="intro-y box col-span-12 lg:col-span-12">
                        <h1 class="text-center mt-5">{{ $title }}</h1>
                        <table class="table table-bordered mt-3">
                            <thead>
                            <tr>
                                <th rowspan="2">S/N</th>
                                <th rowspan="2">Names of MDAs / Sector</th>
                                <th rowspan="2">No. of Commitments</th>
                                <th rowspan="2" class="merged">No. of Outputs</th>
                                <th rowspan="2">No Results to be Delivered</th>
                                <th colspan="2" class="merged">Overall Performance</th>
                                <th rowspan="2">Check</th>
                            </tr>
                            <tr>
                                <th>Performance</th>
                                <th>Rating</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($snapshotData as $data)
                                <tr>
                                    <td>{{ $data['s_n'] }}</td>
                                    <td>{{ $data['sector_name'] }}</td>
                                    <td>{{ $data['no_of_commitments'] }}</td>
                                    <td>{{ $data['no_of_outputs'] }}</td>
                                    <td>{{ $data['outputs_delivered'] }}</td>
                                    <td></td>
                                    <td>{{ $data['rating'] }}</td>
                                    <td></td> <!-- Check placeholder -->
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <!-- END: Latest Uploads -->
                </div>
            </div>

            <div id="change-photo" class="tab-pane" role="tabpanel" aria-labelledby="change-photo-tab">
                <div class="grid grid-cols-12 gap-6">
                    <!-- BEGIN: Latest Uploads -->
                    <div class="intro-y box col-span-12 lg:col-span-12">
                        <h3 class="text-center mt-3">{{ $summaryTitle }}</h3>
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th rowspan="3">S/N</th>
                                <th rowspan="3">Commitments</th>
                                <th rowspan="3">No. of Outputs</th>
                                <th rowspan="3">No Results to be Delivered</th>
                                <th colspan="5" class="merged">Performance for Each Result</th>
                                <th colspan="2" rowspan="2" class="merged">Overall Performance</th>
                                <th rowspan="3">Check</th>
                            </tr>
                            <tr>
                                <th>Exceptional</th>
                                <th>Above Expectation</th>
                                <th>Meets Expectation</th>
                                <th>Needs Improvement</th>
                                <th>Below Minimum</th>
                            </tr>
                            <tr>
                                <th>Above 50%</th>
                                <th>35% - 50%</th>
                                <th>30% - 34%</th>
                                <th>20% - 29%</th>
                                <th>Below 20%</th>
                                <th>Performance</th>
                                <th>Rating</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($summaryData as $ministry => $data)
                                <tr>
                                    <td colspan="12"
                                        style="background-color: #f2f2f2; font-weight: bold;">{{ $ministry }}</td>
                                </tr>
                                @foreach ($data['commitments'] as $commitment)
                                    <tr>
                                        <td>{{ $commitment['s_n'] }}</td>
                                        <td>{{ $commitment['commitment'] }}</td>
                                        <td>{{ $commitment['no_of_outputs'] }}</td>
                                        <td>{{ $commitment['no_results_to_be_delivered'] }}</td>
                                        <td>{{ $commitment['exceptional'] }}</td>
                                        <td>{{ $commitment['above_expectation'] }}</td>
                                        <td>{{ $commitment['meets_expectation'] }}</td>
                                        <td>{{ $commitment['needs_improvement'] }}</td>
                                        <td>{{ $commitment['below_minimum'] }}</td>
                                        <td>{{ $commitment['overall_performance'] }}</td>
                                        <td>{{ $commitment['rating'] }}</td>
                                        <td>{{ $commitment['check'] }}</td>
                                    </tr>
                                @endforeach
                                <tr class="summary-row">
                                    <td>{{ $data['summary']['s_n'] ?? '' }}</td>
                                    <td>{{ $data['summary']['commitment'] }}</td>
                                    <td>{{ $data['summary']['no_of_outputs'] }}</td>
                                    <td>{{ $data['summary']['no_results_to_be_delivered'] }}</td>
                                    <td>{{ $data['summary']['exceptional'] }}</td>
                                    <td>{{ $data['summary']['above_expectation'] }}</td>
                                    <td>{{ $data['summary']['meets_expectation'] }}</td>
                                    <td>{{ $data['summary']['needs_improvement'] }}</td>
                                    <td>{{ $data['summary']['below_minimum'] }}</td>
                                    <td>{{ $data['summary']['overall_performance'] }}</td>
                                    <td>{{ $data['summary']['rating'] }}</td>
                                    <td>{{ $data['summary']['check'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <!-- END: Latest Uploads -->
                </div>
            </div>
            <form action="{{ route('reports.download') }}" method="POST" class="mt-3">
                @csrf
                <div class="grid grid-cols-12 gap-4 gap-y-3 mt-3">
                    <input type="hidden" name="start_month" value="{{ $request->input('start_month') }}">
                    <input type="hidden" name="end_month" value="{{ $request->input('end_month') }}">
                    <input type="hidden" name="year" value="{{ $request->input('year') }}">
                    <div class="col-span-3 sm:col-span-3 mt-5">
                        <input type="submit" class="btn btn-primary w-52" value="Download Report">
                    </div>
                </div>
            </form>
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-3 alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

@endsection
@section('js')
    <script src="code.jquery.com/jquery-3.6.4.min.js"></script>
    <script>
        $(function () {

        });
    </script>

@endsection
