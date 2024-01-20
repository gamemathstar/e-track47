@extends('layouts.app')
@section('content')
    @php
        $user = auth()->user();
        $year = 2024;//\App\Models\StateBudget::currentYear();
        $stateBudget = 300000;//\App\Models\StateBudget::activeBudget();
        $releasedAmount = 40000;//\App\Models\StateBudget::releases();
        $releasedIncomplete = 8;//\App\Models\StateBudget::releaseCount();
        $deliverablesSoFar = 3;//\App\Models\StateBudget::deliveredIn();

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
                <div class="report-box-2 intro-y mt-12 sm:mt-5">
                    <div class="box sm:flex">
                        <div class="px-8 py-12 flex flex-col justify-center flex-1">
                            <div class="h-[290px]">
                                <canvas id="sectorPerformanceChart" width="506" height="580" style="display: block; box-sizing: border-box; height: 290px; width: 253px;"></canvas>
                            </div>
                        </div>
                        <div class="px-8 py-12 flex flex-col justify-center flex-1 border-t sm:border-t-0 sm:border-l border-slate-200 dark:border-darkmode-300 border-dashed">
                            <div class="text-slate-500 text-xs">TOTAL Budget</div>
                            <div class="mt-1.5 flex items-center">
                                <div class="text-base">{{ $stateBudget?number_format($stateBudget):"No Budget Yet" }}</div>
                            </div>
                            <div class="text-slate-500 text-xs mt-5">Total Release</div>
                            <div class="mt-1.5 flex items-center">
                                <div class="text-base">{{ number_format($releasedAmount) }}</div>
                            </div>
                            <div class="text-slate-500 text-xs mt-5">Incompleted Releases</div>
                            <div class="mt-1.5 flex items-center">
                                <div class="text-base">{{$releasedIncomplete}} Sector{{$releasedIncomplete>1?"s":""}}</div>
                            </div>
                            <div class="text-slate-500 text-xs mt-5">Deliverables</div>
                            <div class="mt-1.5 flex items-center">
                                <div class="text-base">{{$deliverablesSoFar}}</div>
                            </div>
{{--                            <div class="text-slate-500 text-xs mt-5">NEW USERS</div>--}}
{{--                            <div class="mt-1.5 flex items-center">--}}
{{--                                <div class="text-base">2.500</div>--}}
{{--                                <div class="text-success flex text-xs font-medium tooltip cursor-pointer ml-2" title="52% Higher than last month"> 52% <i data-lucide="chevron-up" class="w-4 h-4 ml-0.5"></i> </div>--}}
{{--                            </div>--}}
                        </div>
                    </div>
                </div>
            </div>
            <!-- END: General Report -->
            <!-- BEGIN: Sales Report -->
            <div class="col-span-9 lg:col-span-9 xl:col-span-6 mt-2">
                <div class="intro-y flex items-center h-10">
                    <h2 class="text-lg font-medium truncate mr-5">
                        Budget Distribution
                    </h2>
                </div>
                <div class="report-box-2 before:hidden xl:before:block intro-y mt-5">
                    <div class="box p-5">
                        <div class="mt-3">
                            <div class="h-[240px]">
                                <canvas id="myPieChart" class="pie-chart"></canvas>
                            </div>
                        </div>
                        <div class="w-52 sm:w-auto mx-auto mt-8">
                            @foreach(\App\Models\Sector::all() as $sector)
                                <div class="flex items-center mt-4">
                                    <div class="w-2 h-2 bg-primary rounded-full mr-3"></div>

                                    <span class="truncate">{{$sector->name}}</span>
                                    <span class="font-medium ml-auto">{{5}}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 xl:col-span-9 2xl:col-span-9 z-10">

{{--                <div class="mt-14 mb-3 grid grid-cols-12 sm:gap-10 intro-y">--}}
{{--                    <div class="row-start-2 md:row-start-auto col-span-12 md:col-span-12 py-6 border-black border-opacity-10 border-t md:border-t-0 md:border-l md:border-r border-dashed px-10 sm:px-28 md:px-5 -mx-5">--}}
{{--                        <div class="flex flex-wrap items-center">--}}
{{--                            <div class="flex items-center w-full sm:w-auto justify-center sm:justify-start mr-auto mb-5 2xl:mb-0">--}}
{{--                                <div class="w-2 h-2 bg-primary rounded-full -mt-4"></div>--}}
{{--                                <div class="ml-3.5">--}}
{{--                                    <div class="relative text-xl 2xl:text-2xl font-medium leading-6 2xl:leading-5 pl-3.5 2xl:pl-4">--}}
{{--                                        <span class="absolute text-base 2xl:text-xl top-0 left-0 2xl:-mt-1.5">&#8358;</span>--}}
{{--                                         {{number_format($releasedAmount)}}--}}
{{--                                    </div>--}}
{{--                                    <div class="text-slate-500 mt-2">Total Releases</div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="mt-10 text-slate-600 dark:text-slate-300">--}}
{{--                            @if($releasedAmount)--}}
{{--                            You have released 35% of your annual budget for {{$year}}.--}}
{{--                            @else--}}
{{--                                You haven't released any amount for the year.--}}
{{--                            @endif--}}
{{--                        </div>--}}
{{--                        <div class="mt-6">--}}
{{--                            <div class="h-[290px]">--}}
{{--                                <canvas id="report-bar-chart-1" width="506" height="580" style="display: block; box-sizing: border-box; height: 290px; width: 253px;"></canvas>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
            </div>

        </div>
        <div class="report-box-4 w-full h-full grid grid-cols-12 gap-6 xl:absolute -mt-8 xl:mt-0 pb-6 xl:pb-0 top-0 right-0 z-30 xl:z-auto">
            <div class="col-span-12 xl:col-span-3 xl:col-start-10 xl:pb-16 z-30">
                <div class="h-full flex flex-col">
                    <div class="box p-5 mt-6 bg-primary intro-x">
                        <div class="flex flex-wrap gap-3 pb-10">
                            <div class="mr-auto">
                                <div class="text-white text-opacity-70 dark:text-slate-300 flex items-center leading-3"> Total Budget <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" icon-name="alert-circle" data-lucide="alert-circle" class="lucide lucide-alert-circle tooltip w-4 h-4 ml-1.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg> </div>
                                <div class="text-white relative text-2xl font-medium leading-5 pl-4 mt-3.5">
                                    <span class="absolte text-xl top-0 left-0 -mt-1.5">&#8358; {{$stateBudget?number_format($stateBudget):"Budget Not Set" }}</span>

                                </div>
                            </div>
                            @if(!$stateBudget)
                            <a class="flex items-center justify-center w-12 h-12 rounded-full bg-white dark:bg-darkmode-300 bg-opacity-20 hover:bg-opacity-30 text-white" href="">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" icon-name="plus" data-lucide="plus" class="lucide lucide-plus w-6 h-6"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </a>
                                @endif

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')<!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>

        $(function (){
            // alert(3);
            var data = {
                labels: [
                    @foreach(\App\Models\Sector::all() as $sector)
                    '{{$sector->name }}',//distribution
                    @endforeach
                ],
                datasets: [{
                    data: [
                        @foreach(\App\Models\Sector::all() as $sector)
                        {{40}},
                        @endforeach

                    ],
                    backgroundColor: ["#FF6384", "#36A2EB", "#FFCE56", "#4CAF50", "#9966FF"]
                }]
            };


            // Get the canvas element
            var ctx = document.getElementById('myPieChart').getContext('2d');

            // Create a new pie chart using Chart.js
            var myPieChart = new Chart(ctx, {
                type: 'pie',
                data: data
            });


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

            doKpiPerformance(2024)

            function doKpiPerformance(year){

                $.ajax({
                    type:'get',
                    data:{year:year},
                    url:"{{route('chart.sector.kpi.performance')}}",
                    success:function (data) {
                        // Extract data from the response
                        const sectorNames = data.map(sector => sector.sector_name);
                        const kpiCounts = data.map(sector => {
                            // Count KPIs with confirmed performance tracking for each sector
                            return sector.commitments.reduce((count, commitment) => {
                                return count + commitment.deliverables.reduce((deliverableCount, deliverable) => {
                                    return deliverableCount + deliverable.kpis.reduce((kpiCount, kpi) => {
                                        // Check if there is at least one confirmed performance tracking for the KPI
                                        console.log(kpi.performance_tracking.length);
                                        return kpiCount + kpi.performance_tracking.length;
                                    }, 0);
                                }, 0);
                            }, 0);
                        });

                        console.log(kpiCounts);
                        // Access the chart and update its data
                        const chart = myChart; // Access your chart instance (make sure it's in the global scope)
                        chart.data.labels = sectorNames;
                        chart.data.datasets[0].data = kpiCounts;
                        chart.update(); // Update the chart to reflect the new data

                    }
                });
            }
        });

    </script>

@endsection
