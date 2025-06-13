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

    @if (isset($reportData))
        <div class="intro-y grid grid-cols-12 gap-5 mt-5">
            <div class="col-span-12 lg:col-span-12 2xl:col-span-12">
                <div class="box p-5 rounded-md">
                    <div class="flex items-center border-slate-200/60 dark:border-darkmode-400">
                        <div class="text-primary text-2xl">Generated Report</div>
                    </div>
                    <h1 class="text-center mt-5">{{ $title }}</h1>
                    <table class="table table-bordered mt-3">
                        <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Names of MDAs / Sector</th>
                            <th>No. of Commitments</th>
                            <th colspan="2" class="merged">No. of Outputs</th>
                            <th colspan="2" class="merged">Overall Performance</th>
                            <th>Check</th>
                        </tr>
                        <tr>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th>No. of Outputs</th>
                            <th>No Results to be Delivered</th>
                            <th>Performance</th>
                            <th>Rating</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($reportData as $data)
                            <tr>
                                <td>{{ $data['s_n'] }}</td>
                                <td>{{ $data['sector_name'] }}</td>
                                <td>{{ $data['no_of_commitments'] }}</td>
                                <td>{{ $data['no_of_outputs'] }}</td>
                                <td>{{ $data['outputs_delivered'] }}</td>
                                <td></td> <!-- Performance placeholder -->
                                <td>{{ $data['rating'] }}</td>
                                <td></td> <!-- Check placeholder -->
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
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
                    @if ($errors->any())
                        <div class="mt-4 text-red-600">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <br><br>
                </div>
            </div>
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
