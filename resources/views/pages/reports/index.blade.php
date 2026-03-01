@php use Carbon\Carbon; @endphp
@extends('layouts.app')

@section('css')
    <script id="tailwind-config">
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#008751",
                        "background-light": "#f6f6f8",
                    },
                    fontFamily: {
                        "display": ["Public Sans", "sans-serif"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&amp;display=swap"
          rel="stylesheet"/>
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet"/>
    <style>
        body {
            font-family: 'Public Sans', sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .active-nav {
            background-color: rgba(0, 135, 81, 0.2);
            border-left: 4px solid #008751;
        }

        .chart-bar {
            transition: height 0.3s ease;
        }
    </style>
@endsection

@section('content')
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8 mb-4">
        <h2 class="text-lg font-medium mr-auto">
            Reports
            @if($userSector ?? null)
                <span class="text-sm text-gray-500 ml-2">({{ $userSector->sector_name }} Sector)</span>
            @endif
        </h2>
        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <a href="{{ route('reports.comprehensive') }}" class="btn btn-primary mr-2">
                <i class="w-4 h-4 mr-2" data-lucide="bar-chart-3"></i>
                Comprehensive KPI Report
            </a>
            <a href="{{ route('reports.word.form') }}" class="btn btn-primary mr-2">
                <i class="w-4 h-4 mr-2" data-lucide="file-text"></i>
                Generate Word Report
            </a>
        </div>
    </div>
    <!-- Dynamic Content Section -->
    <div class="p-8 space-y-8">
        <!-- Filters Section -->
        <section class="bg-white p-6 rounded-xl border border-primary/10 shadow-sm">
            <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">filter_alt</span>
                Global Performance Filters
            </h2>
            <form id="statisticsFilterForm" method="GET" action="{{ route('reports.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Sectors /
                            MDAs</label>
                        <div class="relative">
                            <select name="sector_id" id="filter_sector"
                                    class="w-full bg-background-light border-primary/20 rounded-lg text-sm focus:ring-primary focus:border-primary appearance-none py-2 pl-3 pr-10">
                                @if($hasAccessToAllSectors ?? false)
                                    <option value="">All Sectors</option>
                                @endif
                                @foreach($sectors ?? [] as $sector)
                                    <option
                                        value="{{ $sector->id }}" {{ request('sector_id') == $sector->id ? 'selected' : '' }}>{{ $sector->sector_name }}</option>
                                @endforeach
                            </select>
                            <span
                                class="material-symbols-outlined absolute right-3 top-2 text-slate-400 pointer-events-none">expand_more</span>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Fiscal Year</label>
                        <div class="relative">
                            <select name="year" id="filter_year"
                                    class="w-full bg-background-light border-primary/20 rounded-lg text-sm focus:ring-primary focus:border-primary appearance-none py-2 pl-3 pr-10">
                                @php
                                    $defaultYear = $activeFramework ? $activeFramework->year : date('Y');
                                @endphp
                                @foreach(range(date('Y'), 2024) as $yr)
                                    <option
                                        value="{{ $yr }}" {{ request('year', $defaultYear) == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                                @endforeach
                            </select>
                            <span
                                class="material-symbols-outlined absolute right-3 top-2 text-slate-400 pointer-events-none">expand_more</span>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Quarter</label>
                        <div
                            class="flex p-1 bg-background-light border border-primary/20 rounded-lg"
                            id="quarterSelector">
                            <input type="hidden" name="quarter" id="selected_quarter"
                                   value="{{ request('quarter', 'all') }}">
                            <button type="button" data-quarter="all"
                                    class="quarter-btn flex-1 py-1 text-xs font-bold rounded-md transition-colors hover:bg-primary/10 {{ request('quarter', 'all') == 'all' ? 'bg-primary text-white shadow-lg shadow-primary/20' : '' }}">
                                Annual
                            </button>
                            <button type="button" data-quarter="1"
                                    class="quarter-btn flex-1 py-1 text-xs font-bold rounded-md transition-colors hover:bg-primary/10 {{ request('quarter') == 1 ? 'bg-primary text-white shadow-lg shadow-primary/20' : '' }}">
                                Q1
                            </button>
                            <button type="button" data-quarter="2"
                                    class="quarter-btn flex-1 py-1 text-xs font-bold rounded-md transition-colors hover:bg-primary/10 {{ request('quarter') == 2 ? 'bg-primary text-white shadow-lg shadow-primary/20' : '' }}">
                                Q2
                            </button>
                            <button type="button" data-quarter="3"
                                    class="quarter-btn flex-1 py-1 text-xs font-bold rounded-md transition-colors hover:bg-primary/10 {{ request('quarter') == 3 ? 'bg-primary text-white shadow-lg shadow-primary/20' : '' }}">
                                Q3
                            </button>
                            <button type="button" data-quarter="4"
                                    class="quarter-btn flex-1 py-1 text-xs font-bold rounded-md transition-colors hover:bg-primary/10 {{ request('quarter') == 4 ? 'bg-primary text-white shadow-lg shadow-primary/20' : '' }}">
                                Q4
                            </button>
                        </div>
                    </div>
                    {{--                    <div class="flex items-end">--}}
                    {{--                        <button type="submit"--}}
                    {{--                                class="w-full bg-primary/20 hover:bg-primary/30 text-primary font-bold py-2 rounded-lg text-sm transition-colors border border-primary/30">--}}
                    {{--                            Apply View Filters--}}
                    {{--                        </button>--}}
                    {{--                    </div>--}}
                </div>
            </form>
        </section>
        <!-- Scorecards -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Average Performance -->
            <div
                class="bg-white p-6 rounded-xl border border-primary/10 shadow-sm relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <span class="material-symbols-outlined text-[120px] text-primary">analytics</span>
                </div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Average Performance</p>
                <div class="mt-4 flex items-end justify-between">
                    <div>
                        <h3 class="text-4xl font-black text-primary">{{ $stats['avg_performance'] ?? 0 }}%</h3>
                        <p class="text-xs mt-1 text-green-500 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">trending_up</span>
                            {{ ($quarter ?? 'all') == 'all' ? 'All Quarters' : 'Q' . $quarter }} {{ $year ?? date('Y') }}
                        </p>
                    </div>
                    <div class="h-12 w-24 flex items-end gap-1 pb-1">
                        <div class="w-full bg-primary/10 rounded-t-sm h-[40%]"></div>
                        <div class="w-full bg-primary/20 rounded-t-sm h-[60%]"></div>
                        <div class="w-full bg-primary/40 rounded-t-sm h-[50%]"></div>
                        <div class="w-full bg-primary/60 rounded-t-sm h-[80%]"></div>
                        <div class="w-full bg-primary rounded-t-sm h-full"></div>
                    </div>
                </div>
            </div>
            <!-- Top Performing Sector -->
            <div
                class="bg-white p-6 rounded-xl border border-primary/10 shadow-sm relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <span class="material-symbols-outlined text-[120px] text-primary">workspace_premium</span>
                </div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Top Performing Sector</p>
                <div class="mt-4">
                    <h3 class="text-xl font-bold">{{ $stats['top_sector']->sector_name ?? 'N/A' }}</h3>
                    <p class="text-xs mt-1 text-slate-400">
                        @if($stats['top_sector'] ?? null)
                            {{ $stats['top_sector']->kpi_count ?? 0 }} KPIs tracked
                        @else
                            No data available
                        @endif
                    </p>
                    @if($stats['top_sector'] ?? null)
                        <div class="w-full bg-primary/10 rounded-full h-1.5 mt-4 overflow-hidden">
                            <div class="bg-primary h-full rounded-full"
                                 style="width: {{ min(100, $stats['top_sector']->avg_performance ?? 0) }}%"></div>
                        </div>
                    @endif
                </div>
            </div>
            <!-- Pending Verifications -->
            <div
                class="bg-white p-6 rounded-xl border border-primary/10 shadow-sm relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <span class="material-symbols-outlined text-[120px] text-primary">pending_actions</span>
                </div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Pending Verifications</p>
                <div class="mt-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-4xl font-black text-amber-500">{{ $stats['pending_verifications'] ?? 0 }}</h3>
                        <p class="text-xs mt-1 text-slate-400">MDAs requiring field review</p>
                    </div>
                    <a href="{{ route('delivery.awaiting.verification') }}"
                       class="bg-amber-500/10 hover:bg-amber-500/20 text-amber-500 px-3 py-1.5 rounded-lg text-xs font-bold border border-amber-500/20 transition-colors">
                        Review List
                    </a>
                </div>
            </div>
        </section>
        <!-- Charts Section -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Bar Chart: Sector Comparison -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl border border-primary/10 shadow-sm">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-lg font-bold">Sector-Wide Performance Comparison</h3>
                        <p class="text-sm text-slate-500">Comparison of Planned vs. Actual achievement rates (%)</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs font-bold">
                        {{--                        <div class="flex items-center gap-1.5"><span--}}
                        {{--                                class="w-3 h-3 rounded-sm bg-primary/20 border border-primary/30"></span> Target--}}
                        {{--                        </div>--}}
                        <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-primary"></span>
                            Actual
                        </div>
                    </div>
                </div>
                <div class="space-y-6">
                    @forelse($stats['sector_comparison'] ?? [] as $sector)
                        <div class="grid grid-cols-[120px_1fr] items-center gap-4">
                            <span class="text-sm font-medium text-slate-400">{{ $sector->sector_name }}</span>
                            <div class="space-y-1">
                                {{--                                <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden flex">--}}
                                {{--                                    <div class="bg-primary/20 h-full border-r border-primary/30"--}}
                                {{--                                         style="width: 100%"></div>--}}
                                {{--                                </div>--}}
                                <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden flex">
                                    <div class="bg-primary h-full"
                                         style="width: {{ min(100, $sector->avg_performance ?? 0) }}%"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-slate-500">
                            <p>No performance data available for the selected filters.</p>
                        </div>
                    @endforelse
                </div>
                <div class="mt-8 pt-6 border-t border-primary/10 flex justify-between">
                    <button
                        class="text-xs font-bold text-slate-500 flex items-center gap-1 hover:text-primary transition-colors">
                        <span class="material-symbols-outlined text-sm">fullscreen</span> View Expanded Analytics
                    </button>
                    <span class="text-[10px] text-slate-500 italic">Data last synced: 15 mins ago</span>
                </div>
            </div>
            <!-- Circular Chart / Status Breakdown -->
            <div class="bg-white p-6 rounded-xl border border-primary/10 shadow-sm flex flex-col">
                <h3 class="text-lg font-bold mb-1">KPI Status Mix</h3>
                <p class="text-sm text-slate-500 mb-8">Overall distribution of goal statuses</p>
                <div class="flex-1 flex flex-col items-center justify-center relative">
                    <!-- Custom CSS Ring Chart Simulation -->
                    <div
                        class="relative w-48 h-48 rounded-full border-[16px] border-slate-200 flex items-center justify-center">
                        @php
                            $kpiBreakdown = $stats['kpi_status_breakdown'] ?? [];
                            $onTrackPct = $kpiBreakdown['on_track_pct'] ?? 0;
                            $atRiskPct = $kpiBreakdown['at_risk_pct'] ?? 0;
                            $delayedPct = $kpiBreakdown['delayed_pct'] ?? 0;
                            $totalPct = $onTrackPct + $atRiskPct + $delayedPct;
                            $onTrackAngle = $totalPct > 0 ? ($onTrackPct / $totalPct) * 360 : 0;
                        @endphp
                        <div
                            class="absolute inset-0 rounded-full border-[16px] border-primary border-t-transparent border-l-transparent transform -rotate-45"
                            style="border-color: #008751; border-top-color: transparent; border-left-color: transparent; transform: rotate(-45deg);">
                        </div>
                        <div class="text-center">
                            <span class="text-3xl font-black">{{ $kpiBreakdown['total'] ?? 0 }}</span>
                            <p class="text-[10px] uppercase font-bold text-slate-500 tracking-tighter leading-none">
                                Total KPIs</p>
                        </div>
                    </div>
                    <div class="mt-8 w-full space-y-3">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-primary"></span>
                                <span class="font-medium">On Track</span>
                            </div>
                            <span class="font-bold">{{ $onTrackPct }}%</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                <span class="font-medium">At Risk</span>
                            </div>
                            <span class="font-bold">{{ $atRiskPct }}%</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                                <span class="font-medium">Delayed</span>
                            </div>
                            <span class="font-bold">{{ $delayedPct }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- Detailed Breakdown Table -->
        {{--        <section class="bg-white rounded-xl border border-primary/10 shadow-sm overflow-hidden">--}}
        {{--            <div class="px-6 py-4 border-b border-primary/10 flex items-center justify-between">--}}
        {{--                <h3 class="text-lg font-bold">Performance Breakdown by MDA</h3>--}}
        {{--                <div class="flex items-center gap-3">--}}
        {{--                    <div class="relative group">--}}
        {{--                        <input--}}
        {{--                            class="bg-background-light border-primary/20 rounded-lg text-xs py-1.5 pl-8 focus:ring-primary focus:border-primary w-64"--}}
        {{--                            placeholder="Search MDA or KPI..." type="text"/>--}}
        {{--                        <span--}}
        {{--                            class="material-symbols-outlined absolute left-2.5 top-1.5 text-slate-500 text-lg">search</span>--}}
        {{--                    </div>--}}
        {{--                    <button class="p-1.5 rounded-lg border border-primary/20 hover:bg-primary/10 transition-colors">--}}
        {{--                        <span class="material-symbols-outlined text-slate-400 text-xl">tune</span>--}}
        {{--                    </button>--}}
        {{--                </div>--}}
        {{--            </div>--}}
        {{--            <div class="overflow-x-auto">--}}
        {{--                <table class="w-full text-left border-collapse">--}}
        {{--                    <thead>--}}
        {{--                    <tr class="bg-background-light text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-primary/10">--}}
        {{--                        <th class="px-6 py-4">Sector / Lead MDA</th>--}}
        {{--                        <th class="px-6 py-4">Key Performance Indicator (KPI)</th>--}}
        {{--                        <th class="px-6 py-4 text-center">Baseline</th>--}}
        {{--                        <th class="px-6 py-4 text-center">--}}
        {{--                            Target {{ ($quarter ?? 'all') == 'all' ? '(All Q)' : '(Q' . $quarter . ')' }}</th>--}}
        {{--                        <th class="px-6 py-4 text-center">--}}
        {{--                            Actual {{ ($quarter ?? 'all') == 'all' ? '(All Q)' : '(Q' . $quarter . ')' }}</th>--}}
        {{--                        <th class="px-6 py-4 text-center">Variance</th>--}}
        {{--                        <th class="px-6 py-4 text-right">Status</th>--}}
        {{--                    </tr>--}}
        {{--                    </thead>--}}
        {{--                    <tbody class="text-sm divide-y divide-primary/5">--}}
        {{--                    @php--}}
        {{--                        $detailedBreakdown = $stats['detailed_breakdown'] ?? null;--}}
        {{--                    @endphp--}}
        {{--                    @forelse($detailedBreakdown ?? [] as $row)--}}
        {{--                        @php--}}
        {{--                            $statusClass = '';--}}
        {{--                            $statusText = $row->status ?? 'Pending';--}}
        {{--                            if ($statusText == 'Exceptional' || $statusText == 'Target Met') {--}}
        {{--                                $statusClass = 'bg-primary/20 text-primary';--}}
        {{--                            } elseif ($statusText == 'At Risk') {--}}
        {{--                                $statusClass = 'bg-amber-500/20 text-amber-600';--}}
        {{--                            } elseif ($statusText == 'Delayed') {--}}
        {{--                                $statusClass = 'bg-red-500/20 text-red-500';--}}
        {{--                            } else {--}}
        {{--                                $statusClass = 'bg-slate-200 text-slate-600';--}}
        {{--                            }--}}
        {{--                            $variance = $row->variance ?? null;--}}
        {{--                            $varianceClass = $variance !== null ? ($variance >= 0 ? 'text-green-500' : 'text-red-500') : 'text-slate-400';--}}
        {{--                            $varianceSign = $variance !== null ? ($variance >= 0 ? '+' : '') : '';--}}
        {{--                        @endphp--}}
        {{--                        <tr class="hover:bg-primary/5 transition-colors cursor-pointer group">--}}
        {{--                            <td class="px-6 py-4">--}}
        {{--                                <div class="font-bold">{{ $row->sector_name }}</div>--}}
        {{--                                <div class="text-[10px] text-slate-500">{{ $row->commitment_name ?? 'N/A' }}</div>--}}
        {{--                            </td>--}}
        {{--                            <td class="px-6 py-4 max-w-xs">--}}
        {{--                                <span class="line-clamp-1 font-medium">{{ $row->kpi }}</span>--}}
        {{--                            </td>--}}
        {{--                            <td class="px-6 py-4 text-center text-slate-400">{{ $row->baseline ?? 'N/A' }} {{ $row->unit_of_measurement ?? '' }}</td>--}}
        {{--                            <td class="px-6 py-4 text-center font-bold">{{ $row->target_value ?? 'N/A' }} {{ $row->unit_of_measurement ?? '' }}</td>--}}
        {{--                            <td class="px-6 py-4 text-center font-bold {{ $row->actual_value ? 'text-primary' : 'text-slate-400' }}">--}}
        {{--                                {{ $row->actual_value ?? 'N/A' }} {{ $row->unit_of_measurement ?? '' }}--}}
        {{--                            </td>--}}
        {{--                            <td class="px-6 py-4 text-center">--}}
        {{--                                @if($variance !== null)--}}
        {{--                                    <span--}}
        {{--                                        class="{{ $varianceClass }} text-xs font-bold">{{ $varianceSign }}{{ number_format($variance, 1) }}%</span>--}}
        {{--                                @else--}}
        {{--                                    <span class="text-slate-400 text-xs">-</span>--}}
        {{--                                @endif--}}
        {{--                            </td>--}}
        {{--                            <td class="px-6 py-4 text-right">--}}
        {{--                                <span--}}
        {{--                                    class="px-2 py-0.5 rounded-full {{ $statusClass }} text-[10px] font-bold uppercase">{{ $statusText }}</span>--}}
        {{--                            </td>--}}
        {{--                        </tr>--}}
        {{--                    @empty--}}
        {{--                        <tr>--}}
        {{--                            <td colspan="7" class="px-6 py-8 text-center text-slate-500">--}}
        {{--                                No performance data available for the selected filters.--}}
        {{--                            </td>--}}
        {{--                        </tr>--}}
        {{--                    @endforelse--}}
        {{--                    </tbody>--}}
        {{--                </table>--}}
        {{--            </div>--}}
        {{--            <div--}}
        {{--                class="px-6 py-4 bg-background-light border-t border-primary/10 flex items-center justify-between">--}}
        {{--                @php--}}
        {{--                    $detailedBreakdown = $stats['detailed_breakdown'] ?? null;--}}
        {{--                @endphp--}}
        {{--                @if($detailedBreakdown)--}}
        {{--                    <p class="text-[11px] text-slate-500">--}}
        {{--                        Showing {{ $detailedBreakdown->firstItem() ?? 0 }} to {{ $detailedBreakdown->lastItem() ?? 0 }}--}}
        {{--                        of {{ $detailedBreakdown->total() }} records analyzed--}}
        {{--                        in {{ ($quarter ?? 'all') == 'all' ? 'All Quarters' : 'Q' . $quarter }} {{ $year ?? date('Y') }}--}}
        {{--                    </p>--}}
        {{--                    <div class="flex items-center gap-2">--}}
        {{--                        @if($detailedBreakdown->onFirstPage())--}}
        {{--                            <button--}}
        {{--                                class="p-1.5 rounded-md border border-primary/20 hover:bg-primary/10 transition-colors disabled:opacity-50 cursor-not-allowed"--}}
        {{--                                disabled>--}}
        {{--                                <span class="material-symbols-outlined text-lg">chevron_left</span>--}}
        {{--                            </button>--}}
        {{--                        @else--}}
        {{--                            <a href="{{ $detailedBreakdown->previousPageUrl() }}"--}}
        {{--                               class="p-1.5 rounded-md border border-primary/20 hover:bg-primary/10 transition-colors">--}}
        {{--                                <span class="material-symbols-outlined text-lg">chevron_left</span>--}}
        {{--                            </a>--}}
        {{--                        @endif--}}

        {{--                        <span--}}
        {{--                            class="text-xs font-bold px-2">Page {{ $detailedBreakdown->currentPage() }} of {{ $detailedBreakdown->lastPage() }}</span>--}}

        {{--                        @if($detailedBreakdown->hasMorePages())--}}
        {{--                            <a href="{{ $detailedBreakdown->nextPageUrl() }}"--}}
        {{--                               class="p-1.5 rounded-md border border-primary/20 hover:bg-primary/10 transition-colors">--}}
        {{--                                <span class="material-symbols-outlined text-lg">chevron_right</span>--}}
        {{--                            </a>--}}
        {{--                        @else--}}
        {{--                            <button--}}
        {{--                                class="p-1.5 rounded-md border border-primary/20 hover:bg-primary/10 transition-colors disabled:opacity-50 cursor-not-allowed"--}}
        {{--                                disabled>--}}
        {{--                                <span class="material-symbols-outlined text-lg">chevron_right</span>--}}
        {{--                            </button>--}}
        {{--                        @endif--}}
        {{--                    </div>--}}
        {{--                @else--}}
        {{--                    <p class="text-[11px] text-slate-500">--}}
        {{--                        No records available--}}
        {{--                    </p>--}}
        {{--                @endif--}}
        {{--            </div>--}}
        {{--        </section>--}}
    </div>

@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('statisticsFilterForm');
            const quarterButtons = document.querySelectorAll('.quarter-btn');
            const selectedQuarterInput = document.getElementById('selected_quarter');
            const sectorSelect = document.getElementById('filter_sector');
            const yearSelect = document.getElementById('filter_year');

            // Function to submit the form
            function submitForm() {
                form.submit();
            }

            // Handle sector dropdown change
            if (sectorSelect) {
                sectorSelect.addEventListener('change', function () {
                    submitForm();
                });
            }

            // Handle year dropdown change
            if (yearSelect) {
                yearSelect.addEventListener('change', function () {
                    submitForm();
                });
            }

            // Handle quarter button selection
            quarterButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const quarter = this.getAttribute('data-quarter');

                    // Update hidden input
                    selectedQuarterInput.value = quarter;

                    // Update button styles
                    quarterButtons.forEach(btn => {
                        btn.classList.remove('bg-primary', 'text-white', 'shadow-lg', 'shadow-primary/20');
                        btn.classList.add('hover:bg-primary/10');
                    });

                    this.classList.remove('hover:bg-primary/10');
                    this.classList.add('bg-primary', 'text-white', 'shadow-lg', 'shadow-primary/20');

                    // Auto-submit form when quarter changes
                    submitForm();
                });
            });
        });
    </script>
@endsection
