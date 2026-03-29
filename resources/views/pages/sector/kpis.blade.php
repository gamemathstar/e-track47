@php use Carbon\Carbon; @endphp
@extends('layouts.app')

@section('css')
    <script id="tailwind-config">
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#008751",
                    },
                    fontFamily: {
                        "display": ["Public Sans", "sans-serif"]
                    },
                },
            },
        }
    </script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700;800;900&display=swap"
          rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
          rel="stylesheet"/>
    <style>
        body {
            font-family: 'Public Sans', sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
@endsection

@section('content')
    @php
        $user = \Illuminate\Support\Facades\Auth::user();
        $totalKpis = $kpis->count();
        $confirmedKpis = 0;
        $totalProgress = 0;
        $kpisWithProgress = 0;

        foreach($kpis as $kpi) {
            $tracks = $kpi->performanceTracking()->get();
            $trgt = $kpi->kpiTargets($year)->first();
            if($trgt && $trgt->target) {
                foreach($tracks as $track) {
                    if($track->confirmation_status == 'Confirmed') {
                        $confirmedKpis++;
                    }
                    if($track->actual_value && $track->milestone && $track->milestone > 0) {
                        $progress = ($track->actual_value / $track->milestone) * 100;
                        $totalProgress += $progress;
                        $kpisWithProgress++;
                    }
                }
            }
        }

        $avgProgress = $kpisWithProgress > 0 ? round($totalProgress / $kpisWithProgress) : 0;
        $lastUpdated = $kpis->flatMap(function($kpi) {
            return $kpi->performanceTracking()->get();
        })->sortByDesc('updated_at')->first();
    @endphp

    <div class="p-8 space-y-6">
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-sm text-slate-500 mb-6">
            <a class="hover:text-primary" href="{{ route('sectors.index') }}">Sectors</a>
            <span class="material-symbols-outlined text-xs">chevron_right</span>
            <a class="hover:text-primary" href="{{ sector_view_url($deliverable->commitment->sector_id ?? 0) }}">
                {{ $deliverable->commitment->sector->sector_name ?? 'Sector' }}
            </a>
            <span class="material-symbols-outlined text-xs">chevron_right</span>
            <a class="hover:text-primary"
               href="{{ commitment_deliverables_url($deliverable->commitment_id ?? 0) }}">
                {{ $deliverable->commitment->title(30) ?? 'Commitment' }}
            </a>
            <span class="material-symbols-outlined text-xs">chevron_right</span>
            <span class="text-slate-900 font-medium">Deliverable KPIs</span>
        </nav>

        <!-- Page Header & Context Card -->
        <div class="bg-white rounded-xl border border-primary/5 overflow-hidden mb-8 shadow-sm">
            <div class="p-6 md:p-8 flex flex-col lg:flex-row gap-8 items-start">
                <div class="flex-1 space-y-4">
                    <div class="space-y-1">
                        <span
                            class="text-primary font-semibold text-xs uppercase tracking-wider">Active Deliverable</span>
                        <h2 class="text-3xl font-extrabold text-slate-900 leading-tight">{{ $deliverable->deliverable }}</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-slate-400 mt-1">account_tree</span>
                            <div>
                                <p class="text-xs text-slate-500 font-medium uppercase">Parent Commitment</p>
                                <p class="text-sm font-semibold text-slate-700">{{ $deliverable->commitment->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        @if($deliverable->budget)
                            <div class="flex items-start gap-3">
                                <span class="material-symbols-outlined text-slate-400 mt-1">attach_money</span>
                                <div>
                                    <p class="text-xs text-slate-500 font-medium uppercase">Budget</p>
                                    <p class="text-sm font-semibold text-slate-700">
                                        &#8358;{{ number_format($deliverable->budget) }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="w-full lg:w-72 flex flex-col gap-3">
                    @if($user->isDeliveryUnit())
                        <button
                            class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-2.5 px-4 rounded-lg flex items-center justify-center gap-2 transition-all shadow-md shadow-primary/20"
                            data-tw-toggle="modal" data-tw-target="#header-footer-modal-preview">
                            <span class="material-symbols-outlined">add</span>
                            Add New KPI
                        </button>
                    @endif
                    @if($user->isDeliveryUnit())
                        <button
                            class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 px-4 rounded-lg flex items-center justify-center gap-2 transition-all"
                            data-tw-toggle="modal" data-tw-target="#targetModal">
                            <span class="material-symbols-outlined">target</span>
                            Set Target
                        </button>
                    @endif
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-slate-600">Year:</label>
                        <select id="changeYear" class="form-control flex-1 text-sm">
                            @foreach(range(2020,date("Y")) as $yr)
                                <option {{$year==$yr?"selected":""}}>{{$yr}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <!-- Quick Progress Stats -->
            <div class="bg-slate-50 border-t border-slate-200 px-8 py-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="flex flex-col">
                    <span class="text-xs text-slate-500">Total KPIs</span>
                    <span class="text-xl font-bold">{{ $totalKpis }}</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-xs text-slate-500">Avg. Progress</span>
                    <span class="text-xl font-bold text-primary">{{ $avgProgress }}%</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-xs text-slate-500">Status</span>
                    <span class="inline-flex items-center gap-1.5 text-sm font-bold text-amber-600">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span> On Track
                    </span>
                </div>
                <div class="flex flex-col">
                    <span class="text-xs text-slate-500">Last Updated</span>
                    <span class="text-sm font-semibold">
                        @if($lastUpdated)
                            {{ Carbon::parse($lastUpdated->updated_at)->diffForHumans() }}
                        @else
                            N/A
                        @endif
                    </span>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div
                class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" class="text-emerald-600 hover:text-emerald-800"
                        onclick="this.parentElement.remove()">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>
        @endif
        @if(session('failure'))
            <div
                class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined">error</span>
                    <span>{{ session('failure') }}</span>
                </div>
                <button type="button" class="text-red-600 hover:text-red-800" onclick="this.parentElement.remove()">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>
        @endif

        <!-- KPI Table Section -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900">KPI Performance Tracking</h3>
                <div class="flex gap-2">
                    <button
                        class="p-2 bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 transition-colors">
                        <span class="material-symbols-outlined">filter_list</span>
                    </button>
                    <button
                        class="p-2 bg-white border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 transition-colors">
                        <span class="material-symbols-outlined">download</span>
                    </button>
                </div>
            </div>
            {{--    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">--}}
            {{--        <h2 class="text-lg font-medium ml-3 mr-auto">Deliverable</h2>--}}
            {{--    </div>--}}
            {{--    <div class="intro-y grid grid-cols-12 gap-5 mt-5">--}}
            {{--        <div class="col-span-12 lg:col-span-12 2xl:col-span-12">--}}
            {{--            <div class="box p-5 rounded-md">--}}
            {{--                <div class="flex items-center border-slate-200/60 dark:border-darkmode-400">--}}
            {{--                    <div class="text-primary text-2xl">{{ $deliverable->deliverable }}</div>--}}
            {{--                </div>--}}
            {{--                &#8358; {{ $deliverable->budget?number_format($deliverable->budget):'Budget Not Set' }}--}}
            {{--                --}}{{--                <button class="btn btn-primary w-24 float-right">Files</button>--}}
            {{--                <br><br>--}}
            {{--            </div>--}}
            {{--        </div>--}}
            {{--    </div>--}}

            <div class="intro-y grid grid-cols-12 gap-5 mt-5">
                <div class="col-span-12 lg:col-span-12 2xl:col-span-12">
                    <div class="rounded-md">
                        {{--                <a href="javascript:;" class="btn btn-primary btn-sm" data-tw-toggle="modal"--}}
                        {{--                   data-tw-target="#header-footer-modal-preview">--}}
                        {{--                    <i data-lucide="edit" class="block mx-auto"></i>--}}
                        {{--                    Add New KPI--}}
                        {{--                </a>--}}
                        {{--                <a href="javascript:;" class="btn btn-primary btn-" data-tw-toggle="modal"--}}
                        {{--                   data-tw-target="#targetModal">--}}
                        {{--                    <i data-lucide="list" class="block mx-auto"></i>--}}
                        {{--                    Targets--}}
                        {{--                </a>--}}
                        {{--                <a href="javascript:;" class="btn">--}}
                        {{--                    Select Target Year <i data-lucide="bar-chart" class="block mx-auto"></i>--}}
                        {{--                    <select name="" id="changeYear" class="form-control btn" style="display: inline-block;width:100px;">--}}
                        {{--                        @foreach(range(2020,date("Y")) as $yr)--}}
                        {{--                            <option {{$year==$yr?"selected":""}}>{{$yr}}</option>--}}
                        {{--                        @endforeach--}}
                        {{--                    </select>--}}
                        {{--                </a>--}}

                        @if(session('success'))
                            <div class="alert alert-success-soft alert-dismissible show flex items-center mb-2 mt-5"
                                 role="alert">
                                <i data-lucide="alert-triangle" class="w-6 h-6 mr-2"></i> {{ session('success') }}
                                <button type="button" class="btn-close" data-tw-dismiss="alert" aria-label="Close">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>
                        @endif
                        @if(session('failure'))
                            <div class="alert alert-danger-soft alert-dismissible show flex items-center mb-2 mt-5"
                                 role="alert"><i
                                    data-lucide="alert-octagon" class="w-6 h-6 mr-2"></i> {{ session('failure') }}
                                <button type="button" class="btn-close" data-tw-dismiss="alert" aria-label="Close">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>
                        @endif
                        @php
                            $user = \Illuminate\Support\Facades\Auth::user();

                        @endphp
                        @if($kpis->count())
                            <div class="bg-white rounded-xl border border-primary/5 overflow-hidden shadow-sm">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                        <tr class="bg-slate-50 border-b border-slate-200">
                                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">
                                                KPI Name
                                            </th>
                                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-center">
                                                Base Value
                                            </th>
                                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-center">
                                                Target
                                            </th>
                                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">
                                                Unit
                                            </th>
                                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-center">
                                                1<sup>st</sup> QPT
                                            </th>
                                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-center">
                                                2<sup>nd</sup> QPT
                                            </th>
                                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-center">
                                                3<sup>rd</sup> QPT
                                            </th>
                                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-center">
                                                4<sup>th</sup> QPT
                                            </th>
                                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-right">
                                                Actions
                                            </th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                        @foreach($kpis as $kpi)
                                            @php
                                                // For PDCU users, only show tracks approved by Sector Head
                                                $onlyApproved = $user->isDeliveryUnit();
                                                // Filter tracks by year
                                                $tracks = $kpi->getYearTracks($year, $onlyApproved);
                                                // Get tracks by quarter for proper display
                                                $q1Track = $kpi->getQuarterTrack(1, $year, $onlyApproved);
                                                $q2Track = $kpi->getQuarterTrack(2, $year, $onlyApproved);
                                                $q3Track = $kpi->getQuarterTrack(3, $year, $onlyApproved);
                                                $q4Track = $kpi->getQuarterTrack(4, $year, $onlyApproved);
                                                $trgt = $kpi->kpiTargets($year)->first();
                                                $latestTrack = $tracks->sortByDesc('updated_at')->first();
                                                $statusBgClass = 'bg-primary';
                                                $statusTextClass = 'text-primary';
                                                $statusText = 'ON TRACK';
                                                if($latestTrack) {
                                                    if($latestTrack->confirmation_status == 'Rejected') {
                                                        $statusBgClass = 'bg-red-500';
                                                        $statusTextClass = 'text-red-700';
                                                        $statusBadgeClass = 'bg-red-100 text-red-700';
                                                        $statusText = 'AT RISK';
                                                    } elseif($latestTrack->confirmation_status == 'Confirmed') {
                                                        $statusBgClass = 'bg-emerald-500';
                                                        $statusTextClass = 'text-emerald-700';
                                                        $statusBadgeClass = 'bg-emerald-100 text-emerald-700';
                                                        $statusText = 'CONFIRMED';
                                                    } else {
                                                        $statusBadgeClass = 'bg-primary/10 text-primary';
                                                    }
                                                } else {
                                                    $statusBadgeClass = 'bg-primary/10 text-primary';
                                                }
                                            @endphp
                                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-1.5 h-10 {{ $statusBgClass }} rounded-full"></div>
                                                        <div>
                                                            <p class="font-bold text-slate-900">{{ $kpi->kpi }}</p>
                                                            <p class="text-xs text-slate-500">
                                                                Year: {{ $kpi->year ?? '---' }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-sm font-bold text-center">{{ $kpi->target_value }}</td>
                                                <td class="px-6 py-4 text-center">
                                                    @php $trgt = $kpi->kpiTargets($year)->first(); @endphp
                                                    <span class="text-sm font-bold">{{$trgt?$trgt->target:"--"}}</span>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-slate-600">{{ $kpi->unit_of_measurement }}</td>
                                                <td class="px-6 py-4 text-center">
                                                    @if($q1Track)
                                                        <a href="javascript:;" data-tw-toggle="modal"
                                                           data-tw-target="#view-performance"
                                                           data-id="{{ $q1Track->id }}"
                                                           data-kpi="{{ $kpi->kpi }}" data-kpi-id="{{$kpi->id}}"
                                                           data-qt="1st QT"
                                                           class="view text-sm font-bold {{ $q1Track->confirmation_status=='Confirmed'?'text-emerald-600':($q1Track->confirmation_status=='Rejected'?'text-red-600':'text-slate-600') }} hover:underline">
                                                            {{ $q1Track->actual_value ?? '-' }}
                                                        </a>
                                                        @if($q1Track->milestone && $q1Track->milestone > 0 && $q1Track->actual_value)
                                                            <div
                                                                class="w-24 bg-slate-100 h-1.5 rounded-full mt-2 mx-auto overflow-hidden">
                                                                <div class="bg-primary h-full"
                                                                     style="width: {{ min(100, ($q1Track->actual_value / $q1Track->milestone) * 100) }}%"></div>
                                                            </div>
                                                        @endif
                                                        {{-- Show plus button for PDCU if no actual_value yet (before Data Admin submits) --}}
                                                        @if($user->isDeliveryUnit() && $q1Track && !$q1Track->actual_value)
                                                            <a href="javascript:"
                                                               class="set-milestone text-primary hover:bg-primary/10 p-2 rounded-lg inline-flex items-center mt-2"
                                                               data-tw-toggle="modal" data-kpi="{{ $kpi->kpi }}"
                                                               data-id="{{ $kpi->id }}" data-quarter="1"
                                                               data-milestone="{{ $q1Track->milestone ?? '' }}"
                                                               data-track-id="{{ $q1Track->id }}"
                                                               data-tw-target="#set-milestone-modal">
                                                                <span
                                                                    class="material-symbols-outlined text-lg">add_circle</span>
                                                            </a>
                                                        @endif
                                                    @elseif($user->isDeliveryUnit())
                                                        {{-- PDCU can create new records with milestone only --}}
                                                        <a href="javascript:"
                                                           class="set-milestone text-primary hover:bg-primary/10 p-2 rounded-lg inline-flex items-center"
                                                           data-tw-toggle="modal" data-kpi="{{ $kpi->kpi }}"
                                                           data-id="{{ $kpi->id }}" data-quarter="1"
                                                           data-milestone=""
                                                           data-tw-target="#set-milestone-modal">
                                                            <span
                                                                class="material-symbols-outlined text-lg">add_circle</span>
                                                        </a>
                                                    @elseif($user->isDataAdmin())
                                                        {{-- Data Admin can only update existing records --}}
                                                        <span class="text-slate-400 text-sm">-</span>
                                                        <small class="text-xs text-slate-500 block">PDCU must create
                                                            first</small>
                                                    @else
                                                        <span class="text-slate-400 text-sm">-</span>
                                                    @endif
                                                    @if($user->isDataAdmin() && $q1Track)
                                                        {{-- Data Admin can update only if: NOT approved by Sector Head OR rejected by Facilitator --}}
                                                        @if(!$q1Track->sector_head_approved_by || ($q1Track->sector_head_approved_by && $q1Track->facilitator_decision === 'Reject'))
                                                            <a href="javascript:"
                                                               class="add text-primary hover:bg-primary/10 p-2 rounded-lg inline-flex items-center mt-2"
                                                               data-tw-toggle="modal" data-kpi="{{ $kpi->kpi }}"
                                                               data-id="{{ $kpi->id }}" data-quarter="1"
                                                               data-track-id="{{ $q1Track->id }}"
                                                               data-milestone="{{ $q1Track->milestone }}"
                                                               data-actual-value="{{ $q1Track->actual_value }}"
                                                               data-remarks="{{ $q1Track->remarks }}"
                                                               data-tracking-date="{{ $q1Track->tracking_date ? \Carbon\Carbon::parse($q1Track->tracking_date)->format('Y-m-d') : '' }}"
                                                               data-tw-target="#add-performance">
                                                                <span
                                                                    class="material-symbols-outlined text-sm">edit</span>
                                                                <span class="text-xs ml-1">Update</span>
                                                            </a>
                                                        @endif
                                                    @endif
                                                    @if($user->isDeliveryUnit() && $q1Track)
                                                        @if($q1Track->actual_value)
                                                            <a href="javascript:"
                                                               class="updM text-primary hover:bg-primary/10 p-1 rounded inline-flex items-center"
                                                               data-tw-toggle="modal" data-kpi="{{ $kpi->kpi }}"
                                                               data-id="{{ $q1Track->id }}"
                                                               data-quarter="{{ $q1Track->quarter }}"
                                                               data-milestone="{{ $q1Track->milestone }}"
                                                               data-actual_value="{{ $q1Track->actual_value }}"
                                                               data-remarks="{{ $q1Track->remarks }}"
                                                               data-delivery_department_value="{{ $q1Track->delivery_department_value }}"
                                                               data-delivery_department_remark="{{ $q1Track->delivery_department_remark }}"
                                                               data-confirmation_status="{{ $q1Track->confirmation_status }}"
                                                               data-tw-target="#update-performance">
                                                                <span
                                                                    class="material-symbols-outlined text-sm">edit</span>
                                                            </a>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    @if($q2Track)
                                                        <a href="javascript:;" data-tw-toggle="modal"
                                                           data-tw-target="#view-performance"
                                                           data-id="{{ $q2Track->id }}"
                                                           data-kpi="{{ $kpi->kpi }}" data-kpi-id="{{$kpi->id}}"
                                                           data-qt="2nd QT"
                                                           class="view text-sm font-bold {{ $q2Track->confirmation_status=='Confirmed'?'text-emerald-600':($q2Track->confirmation_status=='Rejected'?'text-red-600':'text-slate-600') }} hover:underline">
                                                            {{ $q2Track->actual_value ?? '-' }}
                                                        </a>
                                                        @if($q2Track->milestone && $q2Track->milestone > 0 && $q2Track->actual_value)
                                                            <div
                                                                class="w-24 bg-slate-100 h-1.5 rounded-full mt-2 mx-auto overflow-hidden">
                                                                <div class="bg-primary h-full"
                                                                     style="width: {{ min(100, ($q2Track->actual_value / $q2Track->milestone) * 100) }}%"></div>
                                                            </div>
                                                        @endif
                                                        {{-- Show plus button for PDCU if no actual_value yet (before Data Admin submits) --}}
                                                        @if($user->isDeliveryUnit() && $q2Track && !$q2Track->actual_value)
                                                            <a href="javascript:"
                                                               class="set-milestone text-primary hover:bg-primary/10 p-2 rounded-lg inline-flex items-center mt-2"
                                                               data-tw-toggle="modal" data-kpi="{{ $kpi->kpi }}"
                                                               data-id="{{ $kpi->id }}" data-quarter="2"
                                                               data-milestone="{{ $q2Track->milestone ?? '' }}"
                                                               data-track-id="{{ $q2Track->id }}"
                                                               data-tw-target="#set-milestone-modal">
                                                                <span
                                                                    class="material-symbols-outlined text-lg">add_circle</span>
                                                            </a>
                                                        @endif
                                                    @elseif($user->isDeliveryUnit())
                                                        {{-- PDCU can create new records with milestone only --}}
                                                        <a href="javascript:"
                                                           class="set-milestone text-primary hover:bg-primary/10 p-2 rounded-lg inline-flex items-center"
                                                           data-tw-toggle="modal" data-kpi="{{ $kpi->kpi }}"
                                                           data-id="{{ $kpi->id }}" data-quarter="2"
                                                           data-milestone=""
                                                           data-tw-target="#set-milestone-modal">
                                                            <span
                                                                class="material-symbols-outlined text-lg">add_circle</span>
                                                        </a>
                                                    @elseif($user->isDataAdmin())
                                                        {{-- Data Admin can only update existing records --}}
                                                        <span class="text-slate-400 text-sm">-</span>
                                                        <small class="text-xs text-slate-500 block">PDCU must create
                                                            first</small>
                                                    @else
                                                        <span class="text-slate-400 text-sm">-</span>
                                                    @endif
                                                    @if($user->isDataAdmin() && $q2Track)
                                                        {{-- Data Admin can update only if: NOT approved by Sector Head OR rejected by Facilitator --}}
                                                        @if(!$q2Track->sector_head_approved_by || ($q2Track->sector_head_approved_by && $q2Track->facilitator_decision === 'Reject'))
                                                            <a href="javascript:"
                                                               class="add text-primary hover:bg-primary/10 p-2 rounded-lg inline-flex items-center mt-2"
                                                               data-tw-toggle="modal" data-kpi="{{ $kpi->kpi }}"
                                                               data-id="{{ $kpi->id }}" data-quarter="2"
                                                               data-track-id="{{ $q2Track->id }}"
                                                               data-milestone="{{ $q2Track->milestone }}"
                                                               data-actual-value="{{ $q2Track->actual_value }}"
                                                               data-remarks="{{ $q2Track->remarks }}"
                                                               data-tracking-date="{{ $q2Track->tracking_date ? \Carbon\Carbon::parse($q2Track->tracking_date)->format('Y-m-d') : '' }}"
                                                               data-tw-target="#add-performance">
                                                                <span
                                                                    class="material-symbols-outlined text-sm">edit</span>
                                                                <span class="text-xs ml-1">Update</span>
                                                            </a>
                                                        @endif
                                                    @endif
                                                    @if($user->isDeliveryUnit() && $q2Track)
                                                        @if($q2Track->actual_value)
                                                            <a href="javascript:"
                                                               class="updM text-primary hover:bg-primary/10 p-1 rounded inline-flex items-center"
                                                               data-tw-toggle="modal" data-kpi="{{ $kpi->kpi }}"
                                                               data-id="{{ $q2Track->id }}"
                                                               data-quarter="{{ $q2Track->quarter }}"
                                                               data-milestone="{{ $q2Track->milestone }}"
                                                               data-actual_value="{{ $q2Track->actual_value }}"
                                                               data-remarks="{{ $q2Track->remarks }}"
                                                               data-delivery_department_value="{{ $q2Track->delivery_department_value }}"
                                                               data-delivery_department_remark="{{ $q2Track->delivery_department_remark }}"
                                                               data-confirmation_status="{{ $q2Track->confirmation_status }}"
                                                               data-tw-target="#update-performance">
                                                                <span
                                                                    class="material-symbols-outlined text-sm">edit</span>
                                                            </a>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    @if($q3Track)
                                                        <a href="javascript:;" data-tw-toggle="modal"
                                                           data-tw-target="#view-performance"
                                                           data-id="{{ $q3Track->id }}"
                                                           data-kpi="{{ $kpi->kpi }}" data-kpi-id="{{$kpi->id}}"
                                                           data-qt="3rd QT"
                                                           class="view text-sm font-bold {{ $q3Track->confirmation_status=='Confirmed'?'text-emerald-600':($q3Track->confirmation_status=='Rejected'?'text-red-600':'text-slate-600') }} hover:underline">
                                                            {{ $q3Track->actual_value ?? '-' }}
                                                        </a>
                                                        @if($q3Track->milestone && $q3Track->milestone > 0 && $q3Track->actual_value)
                                                            <div
                                                                class="w-24 bg-slate-100 h-1.5 rounded-full mt-2 mx-auto overflow-hidden">
                                                                <div class="bg-primary h-full"
                                                                     style="width: {{ min(100, ($q3Track->actual_value / $q3Track->milestone) * 100) }}%"></div>
                                                            </div>
                                                        @endif
                                                        {{-- Show plus button for PDCU if no actual_value yet (before Data Admin submits) --}}
                                                        @if($user->isDeliveryUnit() && $q3Track && !$q3Track->actual_value)
                                                            <a href="javascript:"
                                                               class="set-milestone text-primary hover:bg-primary/10 p-2 rounded-lg inline-flex items-center mt-2"
                                                               data-tw-toggle="modal" data-kpi="{{ $kpi->kpi }}"
                                                               data-id="{{ $kpi->id }}" data-quarter="3"
                                                               data-milestone="{{ $q3Track->milestone ?? '' }}"
                                                               data-track-id="{{ $q3Track->id }}"
                                                               data-tw-target="#set-milestone-modal">
                                                                <span
                                                                    class="material-symbols-outlined text-lg">add_circle</span>
                                                            </a>
                                                        @endif
                                                    @elseif($user->isDeliveryUnit())
                                                        {{-- PDCU can create new records with milestone only --}}
                                                        <a href="javascript:"
                                                           class="set-milestone text-primary hover:bg-primary/10 p-2 rounded-lg inline-flex items-center"
                                                           data-tw-toggle="modal" data-kpi="{{ $kpi->kpi }}"
                                                           data-id="{{ $kpi->id }}" data-quarter="3"
                                                           data-milestone=""
                                                           data-tw-target="#set-milestone-modal">
                                                            <span
                                                                class="material-symbols-outlined text-lg">add_circle</span>
                                                        </a>
                                                    @elseif($user->isDataAdmin())
                                                        {{-- Data Admin can only update existing records --}}
                                                        <span class="text-slate-400 text-sm">-</span>
                                                        <small class="text-xs text-slate-500 block">PDCU must create
                                                            first</small>
                                                    @else
                                                        <span class="text-slate-400 text-sm">-</span>
                                                    @endif
                                                    @if($user->isDataAdmin() && $q3Track)
                                                        {{-- Data Admin can update only if: NOT approved by Sector Head OR rejected by Facilitator --}}
                                                        @if(!$q3Track->sector_head_approved_by || ($q3Track->sector_head_approved_by && $q3Track->facilitator_decision === 'Reject'))
                                                            <a href="javascript:"
                                                               class="add text-primary hover:bg-primary/10 p-2 rounded-lg inline-flex items-center mt-2"
                                                               data-tw-toggle="modal" data-kpi="{{ $kpi->kpi }}"
                                                               data-id="{{ $kpi->id }}" data-quarter="3"
                                                               data-track-id="{{ $q3Track->id }}"
                                                               data-milestone="{{ $q3Track->milestone }}"
                                                               data-actual-value="{{ $q3Track->actual_value }}"
                                                               data-remarks="{{ $q3Track->remarks }}"
                                                               data-tracking-date="{{ $q3Track->tracking_date ? \Carbon\Carbon::parse($q3Track->tracking_date)->format('Y-m-d') : '' }}"
                                                               data-tw-target="#add-performance">
                                                                <span
                                                                    class="material-symbols-outlined text-sm">edit</span>
                                                                <span class="text-xs ml-1">Update</span>
                                                            </a>
                                                        @endif
                                                    @endif
                                                    @if($user->isDeliveryUnit() && $q3Track)
                                                        @if($q3Track->actual_value)
                                                            <a href="javascript:"
                                                               class="updM text-primary hover:bg-primary/10 p-1 rounded inline-flex items-center"
                                                               data-tw-toggle="modal" data-kpi="{{ $kpi->kpi }}"
                                                               data-id="{{ $q3Track->id }}"
                                                               data-quarter="{{ $q3Track->quarter }}"
                                                               data-milestone="{{ $q3Track->milestone }}"
                                                               data-actual_value="{{ $q3Track->actual_value }}"
                                                               data-remarks="{{ $q3Track->remarks }}"
                                                               data-delivery_department_value="{{ $q3Track->delivery_department_value }}"
                                                               data-delivery_department_remark="{{ $q3Track->delivery_department_remark }}"
                                                               data-confirmation_status="{{ $q3Track->confirmation_status }}"
                                                               data-tw-target="#update-performance">
                                                                <span
                                                                    class="material-symbols-outlined text-sm">edit</span>
                                                            </a>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    @if($q4Track)
                                                        <a href="javascript:;" data-tw-toggle="modal"
                                                           data-tw-target="#view-performance"
                                                           data-id="{{ $q4Track->id }}"
                                                           data-kpi="{{ $kpi->kpi }}" data-kpi-id="{{$kpi->id}}"
                                                           data-qt="4th QT"
                                                           class="view text-sm font-bold {{ $q4Track->confirmation_status=='Confirmed'?'text-emerald-600':($q4Track->confirmation_status=='Rejected'?'text-red-600':'text-slate-600') }} hover:underline">
                                                            {{ $q4Track->actual_value ?? '-' }}
                                                        </a>
                                                        @if($q4Track->milestone && $q4Track->milestone > 0 && $q4Track->actual_value)
                                                            <div
                                                                class="w-24 bg-slate-100 h-1.5 rounded-full mt-2 mx-auto overflow-hidden">
                                                                <div class="bg-primary h-full"
                                                                     style="width: {{ min(100, ($q4Track->actual_value / $q4Track->milestone) * 100) }}%"></div>
                                                            </div>
                                                        @endif
                                                        {{-- Show plus button for PDCU if no actual_value yet (before Data Admin submits) --}}
                                                        @if($user->isDeliveryUnit() && $q4Track && !$q4Track->actual_value)
                                                            <a href="javascript:"
                                                               class="set-milestone text-primary hover:bg-primary/10 p-2 rounded-lg inline-flex items-center mt-2"
                                                               data-tw-toggle="modal" data-kpi="{{ $kpi->kpi }}"
                                                               data-id="{{ $kpi->id }}" data-quarter="4"
                                                               data-milestone="{{ $q4Track->milestone ?? '' }}"
                                                               data-track-id="{{ $q4Track->id }}"
                                                               data-tw-target="#set-milestone-modal">
                                                                <span
                                                                    class="material-symbols-outlined text-lg">add_circle</span>
                                                            </a>
                                                        @endif
                                                    @elseif($user->isDeliveryUnit())
                                                        {{-- PDCU can create new records with milestone only --}}
                                                        <a href="javascript:"
                                                           class="set-milestone text-primary hover:bg-primary/10 p-2 rounded-lg inline-flex items-center"
                                                           data-tw-toggle="modal" data-kpi="{{ $kpi->kpi }}"
                                                           data-id="{{ $kpi->id }}" data-quarter="4"
                                                           data-milestone=""
                                                           data-tw-target="#set-milestone-modal">
                                                            <span
                                                                class="material-symbols-outlined text-lg">add_circle</span>
                                                        </a>
                                                    @elseif($user->isDataAdmin())
                                                        {{-- Data Admin can only update existing records --}}
                                                        <span class="text-slate-400 text-sm">-</span>
                                                        <small class="text-xs text-slate-500 block">PDCU must create
                                                            first</small>
                                                    @else
                                                        <span class="text-slate-400 text-sm">-</span>
                                                    @endif
                                                    @if($user->isDataAdmin() && $q4Track)
                                                        {{-- Data Admin can update only if: NOT approved by Sector Head OR rejected by Facilitator --}}
                                                        @if(!$q4Track->sector_head_approved_by || ($q4Track->sector_head_approved_by && $q4Track->facilitator_decision === 'Reject'))
                                                            <a href="javascript:"
                                                               class="add text-primary hover:bg-primary/10 p-2 rounded-lg inline-flex items-center mt-2"
                                                               data-tw-toggle="modal" data-kpi="{{ $kpi->kpi }}"
                                                               data-id="{{ $kpi->id }}" data-quarter="4"
                                                               data-track-id="{{ $q4Track->id }}"
                                                               data-milestone="{{ $q4Track->milestone }}"
                                                               data-actual-value="{{ $q4Track->actual_value }}"
                                                               data-remarks="{{ $q4Track->remarks }}"
                                                               data-tracking-date="{{ $q4Track->tracking_date ? \Carbon\Carbon::parse($q4Track->tracking_date)->format('Y-m-d') : '' }}"
                                                               data-tw-target="#add-performance">
                                                                <span
                                                                    class="material-symbols-outlined text-sm">edit</span>
                                                                <span class="text-xs ml-1">Update</span>
                                                            </a>
                                                        @endif
                                                    @endif
                                                    @if($user->isDeliveryUnit() && $q4Track)
                                                        @if($q4Track->actual_value)
                                                            <a href="javascript:"
                                                               class="updM text-primary hover:bg-primary/10 p-1 rounded inline-flex items-center"
                                                               data-tw-toggle="modal" data-kpi="{{ $kpi->kpi }}"
                                                               data-id="{{ $q4Track->id }}"
                                                               data-quarter="{{ $q4Track->quarter }}"
                                                               data-milestone="{{ $q4Track->milestone }}"
                                                               data-actual_value="{{ $q4Track->actual_value }}"
                                                               data-remarks="{{ $q4Track->remarks }}"
                                                               data-delivery_department_value="{{ $q4Track->delivery_department_value }}"
                                                               data-delivery_department_remark="{{ $q4Track->delivery_department_remark }}"
                                                               data-confirmation_status="{{ $q4Track->confirmation_status }}"
                                                               data-tw-target="#update-performance">
                                                                <span
                                                                    class="material-symbols-outlined text-sm">edit</span>
                                                            </a>
                                                        @endif
                                                    @endif
                                                </td>
                                                {{--                                                <td class="px-6 py-4">--}}
                                                {{--                                        <span--}}
                                                {{--                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $statusBadgeClass }}">--}}
                                                {{--                                            {{ $statusText }}--}}
                                                {{--                                        </span>--}}
                                                {{--                                                </td>--}}
                                                <td class="px-6 py-4 text-right">
                                                    <div
                                                        class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                        @if($user->isDeliveryUnit())
                                                            <a class="p-1.5 text-amber-600 hover:text-amber-700 rounded hover:bg-amber-50 tooltip edit-kpi"
                                                               data-theme="dark" title="Edit KPI" href="javascript:;"
                                                               data-tw-toggle="modal" data-tw-target="#edit-kpi-modal"
                                                               data-id="{{$kpi->id}}" data-kpi="{{$kpi->kpi}}"
                                                               data-target-value="{{$kpi->target_value}}"
                                                               data-unit-of-measurement="{{$kpi->unit_of_measurement}}"
                                                               data-year="{{$kpi->year ?? ''}}">
                                                                <span
                                                                    class="material-symbols-outlined text-lg">edit</span>
                                                            </a>
                                                            <a class="p-1.5 text-red-600 hover:text-red-700 rounded hover:bg-red-50 tooltip"
                                                               data-theme="dark" title="Delete KPI" href="javascript:;"
                                                               data-tw-toggle="modal"
                                                               data-tw-target="#delete-modal-preview{{ $kpi->id }}">
                                                                <span
                                                                    class="material-symbols-outlined text-lg">delete</span>
                                                            </a>
                                                        @endif
                                                    </div>
                                                    <div id="delete-modal-preview{{$kpi->id}}" class="modal"
                                                         tabindex="-1"
                                                         aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-body p-0">
                                                                    <div class="p-5 text-center"><i
                                                                            data-lucide="x-circle"
                                                                            class="w-16 h-16 text-danger mx-auto mt-3"></i>
                                                                        <div class="text-3xl mt-5">Are you sure?</div>
                                                                        <div class="text-slate-500 mt-2">Do you really
                                                                            want to delete
                                                                            this
                                                                            KPI? <br>
                                                                            <strong>{{$kpi->kpi}}</strong>
                                                                        </div>
                                                                    </div>
                                                                    <div class="px-5 pb-8 text-center">
                                                                        <button type="button" data-tw-dismiss="modal"
                                                                                class="btn btn-outline-secondary w-24 mr-1">
                                                                            Cancel
                                                                        </button>
                                                                        <a href="{{ route('kpis.delete',[$kpi->id]) }}"
                                                                           class="btn btn-danger w-24">Delete</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Pagination / Footer -->
                                <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                                    <p class="text-xs text-slate-500">Showing 1 to {{ $kpis->count() }}
                                        of {{ $kpis->count() }} KPIs</p>
                                </div>
                            </div>
                        @else
                            <div class="bg-white rounded-xl border border-primary/5 shadow-sm p-12 text-center">
                                <span class="material-symbols-outlined text-6xl text-slate-300 mb-4">bar_chart</span>
                                <p class="text-slate-600 font-medium">No KPIs found</p>
                                <p class="text-sm text-slate-400 mt-2">Click <strong class="text-primary">Add New
                                        KPI</strong> to add KPIs.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div id="header-footer-modal-preview" class="modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form action="{{route('deliverable.add.kpi')}}" method="post">
                            @csrf
                            <input type="hidden" name="deliverable_id" value="{{$deliverable->id}}">
                            <!-- BEGIN: Modal Header -->
                            <div class="modal-header">
                                <h2 class="font-medium text-base mr-auto">Add KPI
                                    to {{$deliverable->deliverable}}</h2>

                            </div> <!-- END: Modal Header -->
                            <!-- BEGIN: Modal Body -->
                            <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                                <div class="col-span-12 sm:col-span-12">
                                    <label for="modal-form-1" class="form-label">KPI</label>
                                    <input id="modal-form-1" type="text" class="form-control"
                                           name="kpi" required>
                                </div>
                                <div class="col-span-6 sm:col-span-6">
                                    <label for="modal-form-1" class="form-label">Baseline Value</label>
                                    <input id="modal-form-1" type="number" class="form-control"
                                           name="target_value" step="any" required>
                                </div>
                                <div class="col-span-6 sm:col-span-6">
                                    <label for="modal-form-1" class="form-label">Unit of Measurement</label>
                                    <input id="modal-form-1" type="text" class="form-control"
                                           name="unit_of_measurement" required>
                                </div>
                                <div class="col-span-6 sm:col-span-6">
                                    <label for="modal-form-1" class="form-label">Year</label>
                                    <input id="modal-form-1" type="number" class="form-control"
                                           name="year" min="2000" max="2100" value="{{ date('Y') }}" required>
                                </div>
                            </div> <!-- END: Modal Body -->
                            <!-- BEGIN: Modal Footer -->
                            <div class="modal-footer">
                                <button type="button" data-tw-dismiss="modal"
                                        class="btn btn-outline-secondary w-20 mr-1">Cancel
                                </button>
                                <button type="submit" class="btn btn-primary w-20">Save</button>
                            </div> <!-- END: Modal Footer -->
                        </form>
                    </div>
                </div>
            </div> <!-- END: Modal Content -->

            <div id="add-performance" class="modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form id="add-performance-form" action="{{route('deliverable.store.tracking')}}"
                              enctype="multipart/form-data"
                              method="post">
                            @csrf
                            <input type="hidden" id="kpi_id" name="kpi_id">
                            <input type="hidden" id="track_id" name="track_id">
                            <input type="hidden" id="year" name="year" value="{{$year}}">
                            <!-- BEGIN: Modal Header -->
                            <div class="modal-header">
                                <h2 class="font-medium text-base mr-auto">
                                    Add Performance Tracking to <span id="kpi"></span>
                                </h2>
                                <div class="text-sm text-slate-500">
                                    Year: <span class="font-bold text-slate-700">{{ $year }}</span>
                                </div>
                            </div> <!-- END: Modal Header -->
                            <!-- BEGIN: Modal Body -->
                            <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                                <div class="col-span-6 sm:col-span-6">
                                    <label for="quarter-select" class="form-label">Quarter <span
                                            class="text-red-500">*</span></label>
                                    <select id="quarter-select" name="quarter" class="form-control" required>
                                        <option value="">Select Quarter</option>
                                        <option value="1">Q1 - First Quarter</option>
                                        <option value="2">Q2 - Second Quarter</option>
                                        <option value="3">Q3 - Third Quarter</option>
                                        <option value="4">Q4 - Fourth Quarter</option>
                                    </select>
                                </div>
                                <div class="col-span-6 sm:col-span-6">
                                    <label for="tracking-date" class="form-label">Tracking Date</label>
                                    <input id="tracking-date" type="date" class="form-control"
                                           {{--                                               value="{{$track?Carbon::parse($track->tracking_date)->format('Y-m-d'):''}}"--}}
                                           name="tracking_date" required>
                                </div>
                                <div class="col-span-6 sm:col-span-6">
                                    <label for="milestone" class="form-label">Milestone
                                        @if($user->isDeliveryUnit())
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>
                                    @if($user->isDeliveryUnit())
                                        {{-- PDCU can set milestone when creating new records --}}
                                        <input id="milestone" type="number" class="form-control"
                                               name="milestone"
                                               data-is-pdcu="1"
                                               required
                                               step="any" min="0"
                                               placeholder="Enter milestone value">
                                        <small class="text-slate-500 text-xs mt-1">Set the milestone target for this
                                            quarter</small>
                                    @else
                                        {{-- Data Admin sees milestone as readonly (set by PDCU) --}}
                                        <input id="milestone" type="number" class="form-control"
                                               name="milestone"
                                               data-is-pdcu="0"
                                               readonly
                                               step="any" min="0"
                                               placeholder="Set by PDCU">
                                        <small class="text-slate-500 text-xs mt-1" id="milestone-help-text">Milestone is
                                            set by PDCU and cannot be changed</small>
                                    @endif
                                </div>

                                <div class="col-span-6 sm:col-span-6">
                                    <label for="actual-value" class="form-label">Actual Delivery</label>
                                    <input id="actual-value" type="number" class="form-control"
                                           name="actual_value" step="any"
                                           {{--                                               value="{{ $track?$track->actual_value:'' }}"--}}
                                           {{--                                               placeholder="In {{ $kpi->unit_of_measurement }}"--}}
                                           required>
                                </div>

                                <div class="col-span-6 sm:col-span-6">
                                    <label for="remark" class="form-label">Remark</label>
                                    <textarea name="remarks" id="remark"
                                              class="form-control"></textarea>
                                </div>
                                <div class="col-span-12 sm:col-span-12">
                                    <label for="files" class="form-label">Optional Attachments(s)</label>
                                    <input type="file" name="files[]" id="files" class="form-control mb-2" multiple
                                           accept=".jpg,.jpeg,.png,.xlsx,.xls,.doc,.docx,.pdf">
                                </div>

                                <div class="col-span-12 sm:col-span-12" id="preview"></div>
                            </div> <!-- END: Modal Body -->
                            <!-- BEGIN: Modal Footer -->
                            <div class="modal-footer">
                                <button type="button" data-tw-dismiss="modal"
                                        class="btn btn-outline-secondary w-20 mr-1">Cancel
                                </button>
                                <button type="submit" class="btn btn-primary w-20">Save</button>
                            </div> <!-- END: Modal Footer -->
                        </form>
                    </div>
                </div>
            </div> <!-- END: Modal Content -->

            {{-- PDCU Set Milestone Modal (Simple - Only Milestone) --}}
            @if($user->isDeliveryUnit())
                <div id="set-milestone-modal" class="modal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="set-milestone-form" action="{{route('deliverable.store.tracking')}}"
                                  method="post">
                                @csrf
                                <input type="hidden" id="milestone-kpi-id" name="kpi_id">
                                <input type="hidden" id="milestone-quarter" name="quarter">
                                <input type="hidden" id="milestone-year" name="year" value="{{$year}}">
                                <input type="hidden" id="milestone-track-id" name="track_id">
                                <!-- BEGIN: Modal Header -->
                                <div class="modal-header">
                                    <h2 class="font-medium text-base mr-auto">
                                        Set Milestone for <span id="milestone-kpi-name"></span>
                                    </h2>
                                    <div class="text-sm text-slate-500">
                                        Year: <span class="font-bold text-slate-700">{{ $year }}</span>
                                    </div>
                                </div> <!-- END: Modal Header -->
                                <!-- BEGIN: Modal Body -->
                                <div class="modal-body">
                                    <div class="mb-4">
                                        <label for="milestone-value" class="form-label">Quarter</label>
                                        <div id="milestone-quarter-display"
                                             class="p-2 bg-slate-50 rounded text-sm font-medium"></div>
                                    </div>
                                    <div class="mb-4">
                                        <label for="milestone-value" class="form-label">Milestone <span
                                                class="text-red-500">*</span></label>
                                        <input id="milestone-value" type="number" class="form-control"
                                               name="milestone" step="any" min="0" required
                                               placeholder="Enter milestone value">
                                        <small class="text-slate-500 text-xs mt-1">Set the milestone target for this
                                            quarter. Data Admin will fill in the actual performance data later.</small>
                                    </div>
                                </div> <!-- END: Modal Body -->
                                <!-- BEGIN: Modal Footer -->
                                <div class="modal-footer">
                                    <button type="button" data-tw-dismiss="modal"
                                            class="btn btn-outline-secondary w-20 mr-1">Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary w-20">Save</button>
                                </div> <!-- END: Modal Footer -->
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <div id="update-performance" class="modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form action="{{route('deliverable.tracking.save')}}" method="post">
                            @csrf
                            <input type="hidden" id="track_idX" name="id">
                            <input type="hidden" id="quarterX" name="quarter">
                            <!-- BEGIN: Modal Header -->
                            <div class="modal-header">
                                <h2 class="font-medium text-base mr-auto">
                                    Verify Performance Tracking to <span id="kpi"></span>
                                </h2>

                            </div> <!-- END: Modal Header -->
                            <!-- BEGIN: Modal Body -->
                            <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                                <div class="col-span-6 sm:col-span-6">
                                    <label for="milestone" class="form-label">Milestone:</label>
                                    <div id="milestoneView" class="p-2 bg-slate-50 rounded"></div>
                                </div>
                                <div class="col-span-6 sm:col-span-6">
                                    <label for="milestone" class="form-label">Actual Value:</label>
                                    <div id="actual_valueView"></div>
                                </div>
                                <div class="col-span-6 sm:col-span-6">
                                    <label for="milestone" class="form-label">Quarter:</label>
                                    <div id="quarterView"></div>
                                </div>
                                <div class="col-span-12 sm:col-span-12">
                                    <label for="milestone" class="form-label">Remark:</label>
                                    <div id="remarkView"></div>
                                </div>

                                <div class="col-span-12 sm:col-span-12">
                                    <label class="form-label font-semibold">Attached Evidence(s):</label>
                                    <div id="evidenceView"
                                         class="mt-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                        <p class="text-sm text-gray-500">Loading evidence...</p>
                                    </div>
                                </div>

                                <div class="col-span-12 sm:col-span-12">
                                    <label for="remark" class="form-label">Remark</label>
                                    <textarea name="delivery_department_remark" id="delivery_department_remarkIx"
                                              class="form-control"></textarea>
                                </div>
                                <div class="col-span-12 sm:col-span-12">
                                    <label for="remark" class="form-label">Status</label>
                                    <select name="confirmation_status" id="confirmation_statusIx" required>
                                        <option value="">Select</option>
                                        <option>Confirmed</option>
                                        <option>Rejected</option>
                                    </select>
                                </div>

                            </div> <!-- END: Modal Body -->
                            <!-- BEGIN: Modal Footer -->
                            <div class="modal-footer">
                                <button type="button" data-tw-dismiss="modal"
                                        class="btn btn-outline-secondary w-20 mr-1">Cancel
                                </button>
                                <button type="submit" class="btn btn-primary w-20">Save</button>
                            </div> <!-- END: Modal Footer -->
                        </form>
                    </div>
                </div>
            </div> <!-- END: Modal Content -->

            <div id="view-performance" class="modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="font-medium text-base mr-auto">
                                Performance Tracking for <span id="kpi_title"></span> (<span id="quarter"></span>)
                            </h2>
                        </div>
                        <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                            <div class="col-span-12 sm:col-span-12" id="track-details"></div>
                            <div class="col-span-12 sm:col-span-12 mt-3" id="attachments"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" data-tw-dismiss="modal"
                                    class="btn btn-outline-secondary w-20 mr-1">Close
                            </button>
                            {{--                                <button type="button" class="btn btn-primary w-20">Edit</button>--}}
                        </div>
                    </div>
                </div>
            </div>

            <div id="targetModal" class="modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="font-medium text-base mr-auto">
                                Target for <span id="targetModalYear">{{$year}}</span>
                            </h2>
                        </div>
                        <form action="{{route('kpis.target.save')}}" method="post">
                            @csrf
                            <div class="modal-body">
                                <table class="table table-bordered" style="width: 100%">
                                    <tr>
                                        <th>KPI</th>
                                        <th>Base Value</th>
                                        <th>Target Value</th>
                                    </tr>
                                    @foreach($targets as $target)
                                        <tr>
                                            <td>{{$target->kpi}}</td>
                                            <td>{{$target->target_value}} ({{$target->unit_of_measurement}})</td>
                                            <td>

                                                <input type="text" name="target[{{$target->id}}]"
                                                       class="form-control" value="{{$target->target}}">
                                                ({{$target->unit_of_measurement}})
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" data-tw-dismiss="modal"
                                        class="btn btn-secondary w-20 mr-1">Save
                                </button>
                                <button type="button" data-tw-dismiss="modal"
                                        class="btn btn-outline-secondary w-20 mr-1">Close
                                </button>
                                {{--                                <button type="button" class="btn btn-primary w-20">Edit</button>--}}
                            </div>
                        </form>

                    </div>
                </div>
            </div>

            <!-- Edit KPI Modal -->
            <div id="edit-kpi-modal" class="modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form action="{{route('kpi.update')}}" method="post">
                            @csrf
                            <input type="hidden" name="kpi_id" id="edit-kpi-id">
                            <!-- BEGIN: Modal Header -->
                            <div class="modal-header">
                                <h2 class="font-medium text-base mr-auto">Edit KPI</h2>
                            </div> <!-- END: Modal Header -->
                            <!-- BEGIN: Modal Body -->
                            <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                                <div class="col-span-12 sm:col-span-12">
                                    <label for="edit-kpi-title" class="form-label">KPI</label>
                                    <input id="edit-kpi-title" type="text" class="form-control"
                                           name="kpi" required>
                                </div>
                                <div class="col-span-6 sm:col-span-6">
                                    <label for="edit-kpi-target-value" class="form-label">Baseline Value</label>
                                    <input id="edit-kpi-target-value" type="number" class="form-control"
                                           name="target_value" step="any" required>
                                </div>
                                <div class="col-span-6 sm:col-span-6">
                                    <label for="edit-kpi-unit" class="form-label">Unit of Measurement</label>
                                    <input id="edit-kpi-unit" type="text" class="form-control"
                                           name="unit_of_measurement" required>
                                </div>
                                <div class="col-span-6 sm:col-span-6">
                                    <label for="edit-kpi-year" class="form-label">Year</label>
                                    <input id="edit-kpi-year" type="number" class="form-control"
                                           name="year" min="2000" max="2100" required>
                                </div>
                            </div> <!-- END: Modal Body -->
                            <!-- BEGIN: Modal Footer -->
                            <div class="modal-footer">
                                <button type="button" data-tw-dismiss="modal"
                                        class="btn btn-outline-secondary w-20 mr-1">Cancel
                                </button>
                                <button type="submit" class="btn btn-primary w-20">Update</button>
                            </div> <!-- END: Modal Footer -->
                        </form>
                    </div>
                </div>
            </div> <!-- END: Modal Content -->
        </div>
    </div>

@endsection
@section('js')
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function () {
            // Edit KPI functionality
            $('.edit-kpi').on('click', function () {
                var kpiId = $(this).data('id');
                var kpiTitle = $(this).data('kpi');
                var kpiTargetValue = $(this).data('target-value');
                var kpiUnit = $(this).data('unit-of-measurement');
                var kpiYear = $(this).data('year');

                $('#edit-kpi-id').val(kpiId);
                $('#edit-kpi-title').val(kpiTitle);
                $('#edit-kpi-target-value').val(kpiTargetValue);
                $('#edit-kpi-unit').val(kpiUnit);
                $('#edit-kpi-year').val(kpiYear);
            });

            // Handle Set Milestone Modal (PDCU only - creates record with milestone only)
            $('body .set-milestone').on('click', function () {
                $('#milestone-kpi-name').html($(this).data('kpi'));
                $('#milestone-kpi-id').val($(this).data('id'));
                var quarter = $(this).data('quarter');
                $('#milestone-quarter').val(quarter);

                // Get existing milestone value and track_id if available
                var existingMilestone = $(this).data('milestone');
                var trackId = $(this).data('track-id');

                // Set track_id if it exists (for updates)
                if (trackId) {
                    $('#milestone-track-id').val(trackId);
                } else {
                    $('#milestone-track-id').val('');
                }

                // Display quarter name
                var quarterNames = {
                    '1': 'Q1 - First Quarter',
                    '2': 'Q2 - Second Quarter',
                    '3': 'Q3 - Third Quarter',
                    '4': 'Q4 - Fourth Quarter'
                };
                $('#milestone-quarter-display').html(quarterNames[quarter] || 'Quarter ' + quarter);

                // Clear milestone value
                $('#milestone-value').val('');
            });

            // Handle Set Milestone Form Submission
            $('#set-milestone-form').on('submit', function (e) {
                e.preventDefault();
                var form = $(this);
                var formData = new FormData(this);

                // Debug: Log form data
                console.log('Form data being sent:');
                for (var pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }

                var submitButton = form.find('button[type="submit"]');
                var originalText = submitButton.html();

                submitButton.prop('disabled', true).html('Saving...');
                $('.error-message').remove();
                $('.form-control').removeClass('border-red-500');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function (response) {
                        if (response.success) {
                            // Close the modal immediately by triggering dismiss
                            var modalElement = document.getElementById('set-milestone-modal');
                            if (modalElement) {
                                // Remove the modal backdrop and hide the modal
                                $('body').removeClass('modal-open');
                                $('.modal-backdrop').remove();
                                $('#set-milestone-modal').removeClass('show').hide();
                                $('#set-milestone-modal').attr('aria-hidden', 'true');
                            }

                            // Show success message using SweetAlert before reloading
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message || 'Milestone saved successfully.',
                                showConfirmButton: true,
                                confirmButtonText: 'OK',
                                timer: 2000,
                                timerProgressBar: true
                            }).then(function () {
                                location.reload(); // Reload page to show new data
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'An error occurred.',
                                confirmButtonText: 'OK'
                            });
                            submitButton.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function (xhr) {
                        submitButton.prop('disabled', false).html(originalText);
                        if (xhr.status === 422) {
                            console.log('Validation errors:', xhr.responseJSON);
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                var input = form.find('[name="' + field + '"]');
                                input.addClass('border-red-500');
                                input.after('<div class="error-message text-red-500 text-xs mt-1">' + messages[0] + '</div>');
                            });
                            // Also show alert with all errors
                            var errorMessages = [];
                            $.each(errors, function (field, messages) {
                                errorMessages.push(field + ': ' + messages[0]);
                            });
                            alert('Validation errors:\n' + errorMessages.join('\n'));
                        } else {
                            alert('An error occurred: ' + (xhr.responseJSON?.message || 'Please try again'));
                        }
                    }
                });
            });

            $('body .add').on('click', function () {
                $('#kpi').html($(this).data('kpi'))
                $('#kpi_id').val($(this).data('id'))
                // Set the quarter selector to the clicked quarter
                var quarter = $(this).data('quarter');
                $('#quarter-select').val(quarter);

                // Check if this is an update (has track-id) or new entry
                var trackId = $(this).data('track-id');
                var isPDCU = $('#milestone').data('is-pdcu') === '1' || $('#milestone').data('is-pdcu') === 1;

                if (trackId) {
                    // This is an update (Data Admin updating existing record)
                    $('#track_id').val(trackId);
                    $('#milestone').val($(this).data('milestone') || '');
                    $('#actual-value').val($(this).data('actual-value') || '');
                    $('#remark').val($(this).data('remarks') || '');
                    var trackingDate = $(this).data('tracking-date');
                    if (trackingDate) {
                        $('#tracking-date').val(trackingDate);
                    }

                    // For Data Admin updating, milestone is readonly
                    if (!isPDCU) {
                        $('#milestone').prop('readonly', true);
                    }
                } else {
                    // This is a new entry (PDCU creating new record)
                    $('#track_id').val('');
                    $('#milestone').val('');
                    $('#tracking-date').val('');
                    $('#actual-value').val('');
                    $('#remark').val('');
                    $('#files').val('');
                    $('#preview').empty();

                    // For PDCU creating new, milestone is editable and required
                    if (isPDCU) {
                        $('#milestone').prop('readonly', false);
                        $('#milestone').prop('required', true);
                    }
                }
            });

            // Handle Add Performance Tracking form submission with AJAX
            $('#add-performance-form').on('submit', function (e) {
                e.preventDefault();

                var form = $(this);
                var formData = new FormData(this);
                var submitButton = form.find('button[type="submit"]');
                var originalText = submitButton.html();

                submitButton.prop('disabled', true).html('Saving...');
                $('.error-message').remove();
                $('.form-control').removeClass('border-red-500');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function (response) {
                        if (response.success) {
                            // Reload page to show new data (modal will close automatically on reload)
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                location.reload();
                            }
                        } else {
                            alert(response.message || 'An error occurred.');
                            submitButton.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function (xhr) {
                        submitButton.prop('disabled', false).html(originalText);
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                var input = form.find('[name="' + field + '"]');
                                input.addClass('border-red-500');
                                input.after('<div class="error-message text-red-500 text-xs mt-1">' + messages[0] + '</div>');
                            });
                        } else {
                            var errorMsg = xhr.responseJSON?.message || 'An error occurred. Please try again.';
                            alert(errorMsg);
                        }
                    }
                });
            });

            // Make milestone readonly for non-PDCU users when editing existing records
            $('#add-performance').on('show.bs.modal', function () {
                var trackId = $('#track_id').val();
                var isPDCU = $('#milestone').data('is-pdcu') === '1' || $('#milestone').data('is-pdcu') === 1;
                if (trackId && trackId !== '' && !isPDCU) {
                    $('#milestone').prop('readonly', true);
                    $('#milestone-help-text').text('Milestone can only be set by PDCU users (Coordinator, Deputy Coordinator, or Facilitator)');
                } else if (!isPDCU) {
                    // For new entries, milestone is readonly for non-PDCU
                    $('#milestone').prop('readonly', true);
                    $('#milestone-help-text').text('Milestone can only be set by PDCU users (Coordinator, Deputy Coordinator, or Facilitator)');
                }
            });

            $('body .updM').on('click', function () {
                let trackId = $(this).data('id')
                let milestoneValue = $(this).data('milestone') || '';
                $('#track_idX').val(trackId)
                $('#delivery_department_remarkIx').val($(this).data('delivery_department_remark'))
                $('#confirmation_statusIx').val($(this).data('confirmation_status'))

                // For PDCU users, populate the input field; for others, show in div
                @if($user->isDeliveryUnit())
                $('#milestoneX').val(milestoneValue);
                @else
                $('#milestoneView').html(milestoneValue || '<span class="text-slate-400">Not set</span>');
                @endif

                $('#remarkView').html($(this).data('remarks'))
                $('#quarterView').html($(this).data('quarter'))
                $('#actual_valueView').html($(this).data('actual_value'))

                // Load evidence attachments
                $.get('{{ route('deliverable.kpi.tracking.files',[':id']) }}'.replace(':id', trackId), function (data) {
                    $('#evidenceView').html(data)
                }).fail(function () {
                    $('#evidenceView').html('<p class="text-sm text-gray-500">No evidence attached.</p>')
                })
            });

            // Update target modal year when year selector changes
            $("#changeYear").on("change", function () {
                var selectedYear = $(this).val();
                // Update the modal header immediately
                $('#targetModalYear').text(selectedYear);
                // Reload the page to get updated targets for the new year
                document.location = "{{ deliverable_kpis_url($deliverable->id) }}" + (selectedYear ? "&year=" + selectedYear : "")
            });

            // Update target modal year when "Track Performance" button is clicked
            $('button[data-tw-target="#targetModal"]').on('click', function () {
                var currentYear = $('#changeYear').val();
                $('#targetModalYear').text(currentYear);
            });

            $('.view').on('click', function () {
                $('#quarter').html($(this).data('qt'))
                $('#kpi_title').html($(this).data('kpi'))
                let id = $(this).data('id')
                let kpi = $(this).data('kpi-id')

                $.get('{{ route('performance.tracking', [':kpi',':id']) }}'.replace(':id', id).replace(':kpi', kpi),
                    function (response) {
                        $('#track-details').html(response)
                    }
                )

                $.get('{{ route('deliverable.kpi.tracking.files',[':id']) }}'.replace(':id', id), function (data) {
                    $('#attachments').html(data)
                })
            })


            $(document).on('change', '#files', function () {
                const files = this.files;
                const $table = $('#preview');
                $table.empty(); // Clear previous previews

                Array.from(files).forEach(file => {
                    const fileType = file.type;
                    const fileName = file.name;
                    const fileSizeKB = (file.size / 1024).toFixed(2);

                    const reader = new FileReader();

                    reader.onload = function (e) {
                        let preview;

                        if (['image/jpeg', 'image/png', 'image/jpg'].includes(fileType)) {
                            preview = `<img src="${e.target.result}" class="mt-2 max-w-full h-32 object-contain">`;
                        } else if (fileType === 'application/pdf') {
                            preview = `<iframe src="${e.target.result}" class="mt-2 w-full h-32"></iframe>`;
                        } else {
                            preview = `<div class="mt-2 h-32 bg-gray-200 flex items-center justify-center">
                                <span class="text-gray-500">No preview available</span>
                           </div>`;
                        }

                        const row = `
                <tr>
                    <td style="width: 40%">
                        ${preview}
                    </td>
                    <td>
                        <p class="font-semibold">${fileName}</p>
                        <p class="text-sm text-gray-600">Type: ${fileType}</p>
                        <p class="text-sm text-gray-600">Size: ${fileSizeKB} KB</p>
                        <button type="button" class="btn btn-primary mt-2" disabled>Download</button>
                    </td>
                </tr>
            `;
                        $table.append(row);
                    };

                    reader.readAsDataURL(file);
                });
            })
        })
    </script>
@endsection
