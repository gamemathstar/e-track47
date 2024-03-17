@extends('layouts.app')
@section('content')
    @php
        $user = auth()->user();
        $year = date('Y');//\App\Models\StateBudget::currentYear();
        $stateBudget = \App\Models\Commitment::sum('budget');//\App\Models\StateBudget::activeBudget();
        $releasedAmount = 40000;//\App\Models\StateBudget::releases();
        $releasedIncomplete = 8;//\App\Models\StateBudget::releaseCount();
        $deliverablesSoFar = 3;//\App\Models\StateBudget::deliveredIn();
        $commitments= \App\Models\Commitment::count('id');
        $kpis= \App\Models\Kpi::count('id');
    @endphp
    <div class="relative">
        <div class="grid grid-cols-12 gap-6">
            <!-- BEGIN: General Report -->
            <div class="col-span-12 lg:col-span-10 xl:col-span-9 mt-2">
                <div class="intro-y block sm:flex items-center h-10">
                    <h2 class="text-lg font-medium truncate mr-5">
                        General Report {{$year}}
                    </h2>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-10 xl:col-span-9 mt-2">

                @php
                    $year = date("Y");
                @endphp
                <div class="bg-white p-5 rounded">
                    <table class="table table-bordered" style="font-size: 12px;">
                        <tr>
                            <th rowspan="2">SN</th>
                            <th rowspan="2">Sector</th>
                            <th colspan="4" class="text-center"> {{ $year  }} Performance Score (%)</th>
                        </tr>
                        <tr>
                            <th>1st</th>
                            <th>2nd</th>
                            <th>3rd</th>
                            <th>4th</th>
                        </tr>

                        @foreach(\App\Models\Sector::get() as $sector)
                            @php
                                $commitmentIds = $sector->commitments->pluck('id');
                                $deliverableIds = \App\Models\Deliverable::whereIn('commitment_id',$commitmentIds)->pluck('id');
                                $kpiIds = \App\Models\Kpi::whereIn('deliverable_id',$deliverableIds)->pluck('id');
                                $perf = \App\Models\PerformanceTracking::whereIn('kpi_id',$kpiIds)
                                ->select([
                                    'quarter',
                                    \Illuminate\Support\Facades\DB::raw("SUM( IF( delivery_department_value > 0 AND milestone > 0, (delivery_department_value / milestone) * 100, 0 )) /COUNT(delivery_department_value) AS performance",
                                    )])->where('year',$year)->whereIn('quarter',[1,2,3,4])->groupBy('quarter')->orderBy('quarter')->get();
                            @endphp
                            <tr>
                                <th>{{$loop->iteration}}</th>
                                <th>{{$sector->sector_name}}</th>
                                <th>
                                    @if(isset($perf[0]) && $perf[0]->quarter==1)
                                        <img style="display: inline; height: 16px;"
                                             src="{{ asset('dist/images/arrow-' . ($perf[0]->performance >= 50? 'up':'down') . '.png') }}">
                                        {{ number_format($perf[0]->performance,1)."%" }}
                                    @endif
                                </th>
                                <th>
                                    @if(isset($perf[1]) && $perf[1]->quarter==2)
                                        <img style="display: inline; height: 16px;"
                                             src="{{ asset('dist/images/arrow-' . ($perf[1]->performance >= 50? 'up':'down') . '.png') }}">
                                        {{ number_format($perf[1]->performance,1)."%" }}
                                    @endif
                                </th>
                                <th>
                                    @if(isset($perf[2]) && $perf[2]->quarter==3)
                                        <img style="display: inline; height: 16px;"
                                             src="{{ asset('dist/images/arrow-' . ($perf[2]->performance >= 50? 'up':'down') . '.png') }}">
                                        {{ number_format($perf[2]->performance,1)."%" }}
                                    @endif
                                </th>
                                <th>
                                    @if(isset($perf[3]) && $perf[3]->quarter==4)
                                        <img style="display: inline; height: 16px;"
                                             src="{{ asset('dist/images/arrow-' . ($perf[3]->performance >= 50? 'up':'down') . '.png') }}">
                                        {{ number_format($perf[3]->performance,1)."%" }}
                                    @endif
                                </th>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-10 xl:col-span-9 mt-2">
                <div class="report-box-2 intro-y mt-12 sm:mt-5">
                    <div class="box sm:flex">
                        <div class="px-8 py-12 flex flex-col justify-center flex-1">
                            <div class="h-[290px]">
                                <canvas id="sectorPerformanceChart" width="506" height="580"
                                        style="display: block; box-sizing: border-box; height: 580px; width: 506px;"></canvas>
                            </div>
                        </div>
                        <div
                            class="px-8 py-12 flex flex-col justify-center flex-1 border-t sm:border-t-0 sm:border-l border-slate-200 dark:border-darkmode-300 border-dashed">

                            <div class="h-[290px]">
                                <canvas id="sectorPerformanceChartRatio" width="506" height="580"
                                        style="display: block; box-sizing: border-box; height: 290px; width: 253px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="report-box-2 intro-y mt-12 sm:mt-5">
                    <div class="box sm:flex">
                        <div class="px-8 py-12 flex flex-col justify-center flex-1">
                            <div class="h-[290px]">
                                <h2>Sector-wise Budget Distribution</h2>
                                <canvas id="budgetDistributionChart" width="640" height="640"></canvas>

                            </div>
                        </div>
                        <div
                            class="px-8 py-12 flex flex-col justify-center flex-1 border-t sm:border-t-0 sm:border-l border-slate-200 dark:border-darkmode-300 border-dashed">

                            <div class="h-[290px]">
                                <canvas id="commitmentStatusChart" width="640" height="640"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END: General Report -->
            <!-- BEGIN: Sales Report -->


        </div>
        <div
            class="report-box-4 w-full h-full grid grid-cols-12 gap-6 xl:absolute -mt-8 xl:mt-0 pb-6 xl:pb-0 top-0 right-0 z-30 xl:z-auto">
            <div class="col-span-12 xl:col-span-3 xl:col-start-10 xl:pb-16 z-30">
                <div class="h-full flex flex-col">
                    <div class="box p-5 mt-6 bg-primary intro-x">
                        <div class="flex flex-wrap gap-3 pb-10">
                            <div class="mr-auto">
                                <div class="text-white text-opacity-70 dark:text-slate-300 flex items-center leading-3">
                                    Total Budget
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round" icon-name="alert-circle" data-lucide="alert-circle"
                                         class="lucide lucide-alert-circle tooltip w-4 h-4 ml-1.5">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                </div>
                                <div class="text-white relative text-2xl font-medium leading-5 pl-4 mt-3.5">
                                    <span
                                        class="absolte text-xl top-0 left-0 -mt-1.5">&#8358; {{$stateBudget?number_format($stateBudget):"Budget Not Set" }}</span>

                                </div>
                                <div
                                    class="text-white text-opacity-70 dark:text-slate-300 flex items-center leading-3 mt-5">
                                    Total Commitments
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round" icon-name="alert-circle" data-lucide="alert-circle"
                                         class="lucide lucide-alert-circle tooltip w-4 h-4 ml-1.5">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                </div>
                                <div class="text-white relative text-2xl font-medium leading-5 pl-4 mt-3.5">
                                    <span
                                        class="absolte text-xl top-0 left-0 -mt-1.5">{{$commitments??"No Commitment Added" }}</span>

                                </div>
                                <div
                                    class="text-white text-opacity-70 dark:text-slate-300 flex items-center leading-3 mt-5">
                                    Total KPIs
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round" icon-name="alert-circle" data-lucide="alert-circle"
                                         class="lucide lucide-alert-circle tooltip w-4 h-4 ml-1.5">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                </div>
                                <div class="text-white relative text-2xl font-medium leading-5 pl-4 mt-3.5">
                                    <span
                                        class="absolte text-xl top-0 left-0 -mt-1.5">{{$kpis??"No KPI Added" }}</span>

                                </div>
                            </div>
                            @if(!$stateBudget)
                                <a class="flex items-center justify-center w-12 h-12 rounded-full bg-white dark:bg-darkmode-300 bg-opacity-20 hover:bg-opacity-30 text-white"
                                   href="">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round" icon-name="plus" data-lucide="plus"
                                         class="lucide lucide-plus w-6 h-6">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </a>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>

        $(function () {
            // alert(3);


            // Creating a bar chart (initial setup)
            const ctx2 = document.getElementById('sectorPerformanceChart').getContext('2d');
            const myChart = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: [], // Initially empty
                    datasets: [{
                        label: 'KPI Delivery',
                        data: [],
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 10 // Adjust the max value for better visualization
                        }
                    }
                }
            });


            const ctxRatio = document.getElementById('sectorPerformanceChartRatio').getContext('2d');
            const myChartRatio = new Chart(ctxRatio, {
                type: 'bar',
                data: {
                    labels: [], // Initially empty
                    datasets: [{
                        label: 'KPI Completion Ratio',
                        data: [],
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 1 // Since it's a ratio, set the max value to 1
                        }
                    }
                }
            });

            const ctxBudget = document.getElementById('budgetDistributionChart').getContext('2d');
            const budgetChart = new Chart(ctxBudget, {
                type: 'pie',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: ["#FF6384", "#36A2EB", "#FFCE56", "#4CAF50", "#9966FF"]
                    }]
                },
                options: {
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem, data) {
                                const dataset = data.datasets[tooltipItem.datasetIndex];
                                const label = data.labels[tooltipItem.index];
                                const value = dataset.data[tooltipItem.index];
                                return label + ': $' + value.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'); // Format as currency if needed
                            }
                        }
                    }
                }
            });


            doKpiPerformance(2024)
            doKpiPerformanceRatio()
            distribution()
            pendingCompleted()


            function doKpiPerformanceRatio(year) {
                // chart.sector.kpi.performance.ratio/
                $.ajax({
                    type: 'get',
                    data: {year: year},
                    url: "{{route('chart.sector.kpi.performance.ratio')}}",
                    success: function (data) {
                        // Extract data from the response myChartRatio
                        const sectorNames = data.map(sector => sector.sector_name);
                        const confirmedKpiCounts = data.map(sector => sector.confirmed_kpi_count);
                        const totalKpiCounts = data.map(sector => sector.total_kpi_count);

                        // Calculate the confirmed KPI ratio
                        const confirmedKpiRatio = confirmedKpiCounts.map((confirmedCount, index) => {
                            const totalKpiCount = totalKpiCounts[index];
                            return totalKpiCount === 0 ? 0 : confirmedCount / totalKpiCount;
                        });

                        // Access the chart and update its data
                        const chart = myChartRatio; // Access your chart instance (make sure it's in the global scope)
                        chart.data.labels = sectorNames;
                        chart.data.datasets[0].data = confirmedKpiRatio;
                        chart.update(); // Update the chart to reflect the new data

                    }
                });


            }

            function distribution(year) {

                $.ajax({
                    type: 'get',
                    url: "{{route('chart.sector.budget.distribution')}}",
                    success: function (data) {
                        // Extract data from the response
                        const sectorNamesBudget = data.map(sector => sector.sector_name);
                        const totalBudgets = data.map(sector => sector.total_budget);
                        // Access the chart and update its data
                        const chartBGT = budgetChart; // Access your chart instance (make sure it's in the global scope)
                        chartBGT.data.labels = sectorNamesBudget;
                        chartBGT.data.datasets[0].data = totalBudgets;
                        chartBGT.update(); // Update the chart to reflect the new data

                    }
                });
            }

            function doKpiPerformance(year) {
                $.ajax({
                    type: 'get',
                    data: {year: year},
                    url: "{{route('chart.sector.kpi.performance')}}",
                    success: function (data) {
                        // Extract data from the response
                        const sectorNames = data.map(sector => sector.sector_name);
                        const confirmedKpiCounts = data.map(sector => sector.confirmed_kpi_count);
                        // Access the chart and update its data
                        const chart = myChart; // Access your chart instance (make sure it's in the global scope)
                        chart.data.labels = sectorNames;
                        chart.data.datasets[0].data = confirmedKpiCounts;
                        chart.update(); // Update the chart to reflect the new data

                    }
                });
            }

            function pendingCompleted() {

                $.ajax({
                    type: 'get',
                    url: "{{route('chart.sector.pending.completed')}}",
                    success: function (data) {
                        // Extracting sector names and commitment counts for the chart
                        const sectorNames = data.map(sector => sector.sector_name);
                        const completedCounts = data.map(sector => sector.completed_commitments_count);
                        const pendingCounts = data.map(sector => sector.pending_commitments_count);

// Creating a side-by-side bar chart
                        const ctx = document.getElementById('commitmentStatusChart').getContext('2d');
                        const myChart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: sectorNames,
                                datasets: [{
                                    label: 'Completed Commitments',
                                    data: completedCounts,
                                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    borderWidth: 1
                                }, {
                                    label: 'Pending Commitments',
                                    data: pendingCounts,
                                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                    borderColor: 'rgba(255, 99, 132, 1)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        max: Math.max(...completedCounts, ...pendingCounts) + 1 // Adjust the max value for better visualization
                                    }
                                }
                            }
                        });

                    }
                });

            }
        });

    </script>

@endsection
