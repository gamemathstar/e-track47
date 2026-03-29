@extends('layouts.app')

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&amp;display=swap"
          rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#008751",
                        "background-light": "#f6f6f8",
                        "background-dark": "#101622",
                    },
                    fontFamily: {
                        "display": ["Public Sans"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        .material-icons {
            font-family: 'Material Icons';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-feature-settings: 'liga';
            -webkit-font-smoothing: antialiased;
        }

        /* Ensure Tailwind styles take precedence */
        .content {
            font-family: 'Public Sans', sans-serif;
        }
    </style>
@endsection

@section('content')
    @php
        $user = auth()->user();
        $year = $year ?? date('Y');
        $stateBudget = $stateBudget ?? 0;
        $releasedAmount = 40000;
        $releasedIncomplete = 8;
        $deliverablesSoFar = 3;
        $commitments = $commitments ?? 0;
        $kpis = $kpis ?? 0;
        $hasAccessToAllSectors = $hasAccessToAllSectors ?? false;
        $userSector = $userSector ?? null;
    @endphp

    <div class="p-6 space-y-6">
        <!-- Metric Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Card 1: Total Commitments -->
            <div
                class="bg-white p-6 rounded-xl border border-primary/10 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-slate-600">Total Commitments</p>
                        <h3 class="text-3xl font-bold mt-2 text-slate-900">{{ $commitments ?? "No Commitment Added" }}</h3>
                    </div>
                    <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center text-primary">
                        <span class="material-icons text-3xl">assignment_turned_in</span>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <span class="text-primary text-sm font-bold flex items-center">
                        <span class="material-icons text-sm">trending_up</span> Active
                    </span>
                    <span class="text-xs text-slate-500">Current Period</span>
                </div>
            </div>

            <!-- Card 2: Active KPIs -->
            <div
                class="bg-white p-6 rounded-xl border border-primary/10 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-slate-600">Active KPIs</p>
                        <h3 class="text-3xl font-bold mt-2 text-slate-900">{{ $kpis ?? "No KPI Added" }}</h3>
                    </div>
                    <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center text-primary">
                        <span class="material-icons text-3xl">speed</span>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <span class="text-slate-600 text-sm font-bold">Current Period</span>
                    <span
                        class="text-xs text-slate-500 font-medium px-2 py-0.5 bg-primary/5 rounded">FY {{ $year }}</span>
                </div>
            </div>

            <!-- Card 3: Budget Performance -->
            <div
                class="bg-white p-6 rounded-xl border border-primary/10 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-slate-600">Budget Performance</p>
                        <h3 class="text-3xl font-bold mt-2 text-slate-900">
                            {{ $stateBudget ? '₦' . number_format($stateBudget, 0) : 'N/A' }}
                        </h3>
                    </div>
                    <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center text-primary">
                        <span class="material-icons text-3xl">account_balance_wallet</span>
                    </div>
                </div>
                <div class="mt-4 w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-primary h-full rounded-full" style="width: 74.2%"></div>
                </div>
                <p class="mt-2 text-[10px] text-slate-500 uppercase font-bold tracking-widest">Resource Utilization</p>
            </div>
        </div>

        <!-- Main Report Section -->
        <div class="bg-white rounded-xl border border-primary/10 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-primary/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">General Report {{ $year }}</h2>
                    <p class="text-sm text-slate-600">
                        @if($hasAccessToAllSectors)
                            Quarterly performance breakdown across all government sectors.
                        @elseif($userSector)
                            Quarterly performance breakdown for {{ $userSector->sector_name }}.
                        @else
                            Quarterly performance breakdown for your assigned sector(s).
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <label for="year" class="text-sm font-medium text-slate-600 whitespace-nowrap">Year</label>
                        <select name="year" id="year" onchange="if(this.value) location.href=this.value"
                                class="min-w-[7rem] rounded-lg border border-primary/20 text-slate-900 bg-white px-3 py-2 text-sm font-medium focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            @foreach($years ?? [] as $y)
                                <option value="{{ $yearEncryptedUrls[$y] ?? '#' }}" {{ (int)$y === (int)$year ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('reports.comprehensive') }}"
                       class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 shadow-lg shadow-primary/20 transition-all flex items-center gap-2 text-sm font-bold">
                        <span class="material-icons text-sm">download</span>
                        Export Report
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                    <tr class="bg-slate-50/50 text-slate-600 text-xs uppercase tracking-wider font-bold">
                        <th class="px-6 py-4">SN</th>
                        <th class="px-6 py-4">Sector / MDA</th>
                        <th class="px-6 py-4 text-center">Q1 Score</th>
                        <th class="px-6 py-4 text-center">Q2 Score</th>
                        <th class="px-6 py-4 text-center">Q3 Score</th>
                        <th class="px-6 py-4 text-center">Q4 Score</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-primary/5">
                    @php
                        $rows = $sectorQuarterPerf ?? [];
                    @endphp
                    @foreach($rows as $idx => $row)
                        @php
                            $q1 = $row[1] ?? null;
                            $q2 = $row[2] ?? null;
                            $q3 = $row[3] ?? null;
                            $q4 = $row[4] ?? null;
                            $avg = null;
                            $count = 0;
                            $sum = 0;
                            if (!is_null($q1)) { $count++; $sum += $q1; }
                            if (!is_null($q2)) { $count++; $sum += $q2; }
                            if (!is_null($q3)) { $count++; $sum += $q3; }
                            if (!is_null($q4)) { $count++; $sum += $q4; }
                            if ($count > 0) {
                                $avg = round($sum / $count, 1);
                            }
                        @endphp
                        <tr class="hover:bg-primary/[0.02] transition-colors">
                            <td class="px-6 py-4">
                                <span
                                    class="font-bold text-sm text-slate-900">{{ $loop->iteration }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                                        <span class="material-icons">business</span>
                                    </div>
                                    <div>
                                        <p class="font-bold text-sm text-slate-900">{{ $row['sector_name'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if(!is_null($q1))
                                    <div
                                        class="flex items-center justify-center gap-1.5 {{ $q1 >= 50 ? 'text-primary' : 'text-red-600' }} font-bold">
                                        {{ number_format($q1, 1) }}%
                                        <span
                                            class="material-icons text-sm">{{ $q1 >= 50 ? 'arrow_upward' : 'arrow_downward' }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if(!is_null($q2))
                                    <div
                                        class="flex items-center justify-center gap-1.5 {{ $q2 >= 50 ? 'text-primary' : 'text-red-600' }} font-bold">
                                        {{ number_format($q2, 1) }}%
                                        <span
                                            class="material-icons text-sm">{{ $q2 >= 50 ? 'arrow_upward' : 'arrow_downward' }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if(!is_null($q3))
                                    <div
                                        class="flex items-center justify-center gap-1.5 {{ $q3 >= 50 ? 'text-primary' : 'text-red-600' }} font-bold">
                                        {{ number_format($q3, 1) }}%
                                        <span
                                            class="material-icons text-sm">{{ $q3 >= 50 ? 'arrow_upward' : 'arrow_downward' }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if(!is_null($q4))
                                    <div
                                        class="flex items-center justify-center gap-1.5 {{ $q4 >= 50 ? 'text-primary' : 'text-red-600' }} font-bold">
                                        {{ number_format($q4, 1) }}%
                                        <span
                                            class="material-icons text-sm">{{ $q4 >= 50 ? 'arrow_upward' : 'arrow_downward' }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-slate-50/30 flex items-center justify-between">
                <p class="text-xs text-slate-600 font-medium">Showing {{ count($rows) }} sectors</p>
            </div>
        </div>

        <!-- Charts Section -->
{{--        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">--}}
{{--            <!-- Chart 1: Sector Performance -->--}}
{{--            <div class="bg-white rounded-xl border border-primary/10 shadow-sm p-6">--}}
{{--                <h3 class="text-lg font-bold text-slate-900 mb-4">KPI Delivery Performance</h3>--}}
{{--                <div class="h-[290px]">--}}
{{--                    <canvas id="sectorPerformanceChart" width="506" height="580"></canvas>--}}
{{--                </div>--}}
{{--            </div>--}}

{{--            <!-- Chart 2: KPI Completion Ratio -->--}}
{{--            <div class="bg-white rounded-xl border border-primary/10 shadow-sm p-6">--}}
{{--                <h3 class="text-lg font-bold text-slate-900 mb-4">KPI Completion Ratio</h3>--}}
{{--                <div class="h-[290px]">--}}
{{--                    <canvas id="sectorPerformanceChartRatio" width="506" height="580"></canvas>--}}
{{--                </div>--}}
{{--            </div>--}}

{{--            <!-- Chart 3: Budget Distribution -->--}}
{{--            <div class="bg-white rounded-xl border border-primary/10 shadow-sm p-6">--}}
{{--                <h3 class="text-lg font-bold text-slate-900 mb-4">Sector-wise Budget/Target--}}
{{--                    Distribution</h3>--}}
{{--                <div class="h-[290px]">--}}
{{--                    <canvas id="budgetDistributionChart" width="640" height="640"></canvas>--}}
{{--                </div>--}}
{{--            </div>--}}

{{--            <!-- Chart 4: Commitment Status -->--}}
{{--            <div class="bg-white rounded-xl border border-primary/10 shadow-sm p-6">--}}
{{--                <h3 class="text-lg font-bold text-slate-900 mb-4">Commitment Status</h3>--}}
{{--                <div class="h-[290px]">--}}
{{--                    <canvas id="commitmentStatusChart" width="640" height="640"></canvas>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
    </div>

@endsection

@section('js')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(function () {
            // Creating a bar chart (initial setup)
            const ctx2 = document.getElementById('sectorPerformanceChart').getContext('2d');
            const myChart = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'KPI Delivery',
                        data: [],
                        backgroundColor: 'rgba(0, 135, 81, 0.2)',
                        borderColor: 'rgba(0, 135, 81, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 10
                        }
                    }
                }
            });

            const ctxRatio = document.getElementById('sectorPerformanceChartRatio').getContext('2d');
            const myChartRatio = new Chart(ctxRatio, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'KPI Completion Ratio',
                        data: [],
                        backgroundColor: 'rgba(0, 135, 81, 0.2)',
                        borderColor: 'rgba(0, 135, 81, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 1
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
                        backgroundColor: ["#FF6384", "#36A2EB", "#FFCE56", "#4CAF50", "#9966FF", "#FF9F40", "#FF6384", "#C9CBCF"]
                    }]
                },
                options: {
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem, data) {
                                const dataset = data.datasets[tooltipItem.datasetIndex];
                                const label = data.labels[tooltipItem.index];
                                const value = dataset.data[tooltipItem.index];
                                return label + ': ₦' + value.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                            }
                        }
                    }
                }
            });

            doKpiPerformance({{ $year }})
            doKpiPerformanceRatio()
            distribution()
            pendingCompleted()

            function doKpiPerformanceRatio(year) {
                $.ajax({
                    type: 'get',
                    data: {year: year},
                    url: "{{route('chart.sector.kpi.performance.ratio')}}",
                    success: function (data) {
                        const sectorNames = data.map(sector => sector.sector_name);
                        const confirmedKpiCounts = data.map(sector => sector.confirmed_kpi_count);
                        const totalKpiCounts = data.map(sector => sector.total_kpi_count);

                        const confirmedKpiRatio = confirmedKpiCounts.map((confirmedCount, index) => {
                            const totalKpiCount = totalKpiCounts[index];
                            return totalKpiCount === 0 ? 0 : confirmedCount / totalKpiCount;
                        });

                        const chart = myChartRatio;
                        chart.data.labels = sectorNames;
                        chart.data.datasets[0].data = confirmedKpiRatio;
                        chart.update();
                    }
                });
            }

            function distribution(year) {
                $.ajax({
                    type: 'get',
                    url: "{{route('chart.sector.budget.distribution')}}",
                    success: function (data) {
                        const sectorNamesBudget = data.map(sector => sector.sector_name);
                        const totalBudgets = data.map(sector => sector.total_budget);
                        const chartBGT = budgetChart;
                        chartBGT.data.labels = sectorNamesBudget;
                        chartBGT.data.datasets[0].data = totalBudgets;
                        chartBGT.update();
                    }
                });
            }

            function doKpiPerformance(year) {
                $.ajax({
                    type: 'get',
                    data: {year: year},
                    url: "{{route('chart.sector.kpi.performance')}}",
                    success: function (data) {
                        const sectorNames = data.map(sector => sector.sector_name);
                        const confirmedKpiCounts = data.map(sector => sector.confirmed_kpi_count);
                        const chart = myChart;
                        chart.data.labels = sectorNames;
                        chart.data.datasets[0].data = confirmedKpiCounts;
                        chart.update();
                    }
                });
            }

            function pendingCompleted() {
                $.ajax({
                    type: 'get',
                    url: "{{route('chart.sector.pending.completed')}}",
                    success: function (data) {
                        const sectorNames = data.map(sector => sector.sector_name);
                        const completedCounts = data.map(sector => sector.completed_commitments_count);
                        const pendingCounts = data.map(sector => sector.pending_commitments_count);

                        const ctx = document.getElementById('commitmentStatusChart').getContext('2d');
                        const myChart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: sectorNames,
                                datasets: [{
                                    label: 'Completed Commitments',
                                    data: completedCounts,
                                    backgroundColor: 'rgba(0, 135, 81, 0.2)',
                                    borderColor: 'rgba(0, 135, 81, 1)',
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
                                        max: Math.max(...completedCounts, ...pendingCounts) + 1
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
