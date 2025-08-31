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
                        <td>{{ $data['no_of_kpis'] ?? 0 }}</td>
                        <td>{{ $data['performance'] ?? 0 }}%</td>
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
                    <th rowspan="3">No. of KPIs</th>
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
                            <td>{{ $commitment['no_of_kpis'] ?? 0 }}</td>
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
                        <td>{{ $data['summary']['no_of_kpis'] ?? 0 }}</td>
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

{{--<form action="{{ route('reports.download') }}" method="POST" class="mt-3">--}}
{{--    @csrf--}}
{{--    <div class="grid grid-cols-12 gap-4 gap-y-3 mt-3">--}}
{{--        <input type="hidden" name="start_month" value="{{ $startMonth }}">--}}
{{--        <input type="hidden" name="end_month" value="{{ $endMonth }}">--}}
{{--        <input type="hidden" name="year" value="{{ $year }}">--}}
{{--        <div class="col-span-3 sm:col-span-3 mt-5">--}}
{{--            <input type="submit" class="btn btn-primary w-52" value="Download Report">--}}
{{--        </div>--}}
{{--    </div>--}}
{{--</form>--}}
