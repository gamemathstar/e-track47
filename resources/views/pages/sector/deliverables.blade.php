@php use Carbon\Carbon; @endphp
@extends('layouts.app')

@section('css')
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
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
        $totalDeliverables = $deliverables->count();
        $totalKpis = $deliverables->sum(function($deliverable) {
            return $deliverable->kpis()->count();
        });
        $atRiskDeliverables = $deliverables->where('status', 'At Risk')->count();
        $delayedDeliverables = $deliverables->filter(function($deliverable) {
            if ($deliverable->end_date && $deliverable->status != 'Completed') {
                return Carbon::parse($deliverable->end_date)->isPast();
            }
            return false;
        })->count();
        
        // Calculate overall progress
        $completedDeliverables = $deliverables->where('status', 'Completed')->count();
        $overallProgress = $totalDeliverables > 0 ? round(($completedDeliverables / $totalDeliverables) * 100) : 0;
        
        // Get next milestone (earliest end date for non-completed deliverables)
        $nextMilestone = $deliverables->filter(function($d) {
            return $d->status != 'Completed' && $d->end_date;
        })->sortBy('end_date')->first();
    @endphp

    <div class="p-8 space-y-6">
        <!-- Breadcrumbs -->
        <div class="flex items-center gap-2 mb-6">
            <a class="text-primary hover:underline text-sm font-medium flex items-center gap-1" href="{{ route('sectors.index') }}">
                <span class="material-symbols-outlined text-[16px]">home</span>
                Sectors
            </a>
            <span class="text-slate-400 text-sm">/</span>
            <a class="text-primary hover:underline text-sm font-medium" href="{{ sector_view_url($commitment->sector_id ?? 0) }}">
                {{ $commitment->sector->sector_name ?? 'Sector' }}
            </a>
            <span class="text-slate-400 text-sm">/</span>
            <span class="text-slate-500 text-sm font-medium">{{ $commitment->title(30) }}</span>
        </div>

        <!-- Commitment Header Card -->
        <div class="bg-white rounded-xl border border-primary/5 shadow-sm p-8 mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        @if($commitment->status == 'Completed')
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold uppercase tracking-wider">Completed</span>
                        @elseif($commitment->status == 'In Progress')
                            <span class="px-2.5 py-0.5 rounded-full bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider">In Progress</span>
                        @elseif($commitment->status == 'At Risk')
                            <span class="px-2.5 py-0.5 rounded-full bg-red-100 text-red-700 text-xs font-bold uppercase tracking-wider">At Risk</span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-bold uppercase tracking-wider">Not Started</span>
                        @endif
                    </div>
                    <h1 class="text-slate-900 text-3xl font-black leading-tight tracking-tight mb-2">
                        {{ $commitment->name }}
                    </h1>
                    <p class="text-slate-500 text-base font-normal flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">account_balance</span>
                        {{ $commitment->sector->sector_name ?? 'N/A' }}
                    </p>
                    @if($commitment->description)
                        <p class="text-slate-600 text-sm mt-2">{{ $commitment->description }}</p>
                    @endif
                </div>
                <div class="flex flex-col gap-4 min-w-[280px]">
                    <div class="flex justify-between items-end">
                        <span class="text-sm font-semibold text-slate-700">Overall Progress</span>
                        <span class="text-2xl font-bold text-primary">{{ $overallProgress }}%</span>
                    </div>
                    <div class="h-3 w-full bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-primary rounded-full transition-all" style="width: {{ $overallProgress }}%;"></div>
                    </div>
                    <div class="flex justify-between text-xs text-slate-400 font-medium">
                        <span>{{ $completedDeliverables }}/{{ $totalDeliverables }} Completed</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Bar -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-5 rounded-xl border border-primary/5 flex items-center gap-4">
                <div class="size-12 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[28px]">inventory_2</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Deliverables</p>
                    <p class="text-xl font-black text-slate-900">{{ $totalDeliverables }} Items</p>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-primary/5 flex items-center gap-4">
                <div class="size-12 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600">
                    <span class="material-symbols-outlined text-[28px]">speed</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Active KPIs</p>
                    <p class="text-xl font-black text-slate-900">{{ $totalKpis }} Metrics</p>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-primary/5 flex items-center gap-4">
                <div class="size-12 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600">
                    <span class="material-symbols-outlined text-[28px]">warning</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">At Risk</p>
                    <p class="text-xl font-black text-slate-900">{{ $atRiskDeliverables + $delayedDeliverables }} Delayed</p>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-primary/5 flex items-center gap-4">
                <div class="size-12 rounded-lg bg-purple-100 flex items-center justify-center text-purple-600">
                    <span class="material-symbols-outlined text-[28px]">calendar_today</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Next Milestone</p>
                    <p class="text-xl font-black text-slate-900">
                        @if($nextMilestone && $nextMilestone->end_date)
                            {{ Carbon::parse($nextMilestone->end_date)->format('M d') }}
                        @else
                            N/A
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Deliverables Section -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Deliverables</h2>
                <p class="text-slate-500 text-sm">Detailed list of action items for this commitment</p>
            </div>
            @php
                if (!isset($user)) {
                    $user = \Illuminate\Support\Facades\Auth::user();
                }
            @endphp
            @if($user->isDeliveryUnit())
                <button class="flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-lg font-bold text-sm shadow-md hover:bg-primary/90 transition-all active:scale-95" data-tw-toggle="modal" data-tw-target="#header-footer-modal-preview">
                    <span class="material-symbols-outlined text-[20px]">add_circle</span>
                    Add Deliverable
                </button>
            @endif
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" class="text-emerald-600 hover:text-emerald-800" onclick="this.parentElement.remove()">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>
        @endif
        @if(session('failure'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined">error</span>
                    <span>{{ session('failure') }}</span>
                </div>
                <button type="button" class="text-red-600 hover:text-red-800" onclick="this.parentElement.remove()">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>
        @endif

        <!-- Filters & Search -->
        <div class="flex flex-wrap gap-4 mb-6 bg-slate-50 p-4 rounded-xl border border-primary/5">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-[20px]">search</span>
                    <input class="w-full pl-10 pr-4 py-2 bg-white border-slate-200 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="Filter deliverables..." type="text" id="searchInput"/>
                </div>
            </div>
            <select class="bg-white border-slate-200 rounded-lg text-sm px-4 py-2 focus:ring-primary focus:border-primary" id="statusFilter">
                <option value="">All Statuses</option>
                <option value="Completed">Completed</option>
                <option value="In Progress">In Progress</option>
                <option value="At Risk">At Risk</option>
                <option value="Not Started">Not Started</option>
            </select>
        </div>

        <!-- Deliverables Table -->
        @if($deliverables->count())
            <div class="bg-white rounded-xl border border-primary/5 overflow-hidden shadow-sm">
                <table class="w-full text-left border-collapse" id="deliverablesTable">
                    <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Deliverable Name</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Current Status</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">End Date</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">KPIs</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Progress</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @foreach($deliverables as $deliverable)
                        @php
                            $kpiCount = $deliverable->kpis()->count();
                            $isDelayed = $deliverable->end_date && $deliverable->status != 'Completed' && Carbon::parse($deliverable->end_date)->isPast();
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-5">
                                <div class="flex flex-col">
                                    <span class="text-slate-900 font-bold text-sm">{{ $deliverable->deliverable }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-2">
                                    @if($deliverable->status == 'Completed')
                                        <span class="size-2 rounded-full bg-slate-400"></span>
                                        <span class="text-sm font-semibold text-slate-500">Completed</span>
                                    @elseif($deliverable->status == 'In Progress')
                                        <span class="size-2 rounded-full bg-primary"></span>
                                        <span class="text-sm font-semibold text-primary">On Track</span>
                                    @elseif($deliverable->status == 'At Risk' || $isDelayed)
                                        <span class="size-2 rounded-full bg-amber-500"></span>
                                        <span class="text-sm font-semibold text-amber-500">At Risk</span>
                                    @else
                                        <span class="size-2 rounded-full bg-slate-300"></span>
                                        <span class="text-sm font-semibold text-slate-400">Not Started</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-2 text-slate-700 text-sm">
                                    <span class="material-symbols-outlined text-[18px]">event</span>
                                    @if($deliverable->end_date)
                                        {{ Carbon::parse($deliverable->end_date)->format('M d, Y') }}
                                    @else
                                        <span class="text-slate-400">N/A</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <span class="inline-flex items-center justify-center size-8 rounded-full bg-slate-100 text-slate-700 font-bold text-xs">{{ $kpiCount }}</span>
                            </td>
                            <td class="px-6 py-5 text-center">
                                @if($deliverable->status != 'Not Started')
                                    <span class="text-sm font-medium text-slate-600">{{ $deliverable->progress() }}</span>
                                @else
                                    <span class="text-sm font-medium text-slate-400">- - -</span>
                                @endif
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ deliverable_kpis_url($deliverable->id) }}" class="text-primary hover:bg-primary/10 px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1 transition-colors">
                                        <span class="material-symbols-outlined text-[16px]">visibility</span>
                                        View KPIs
                                    </a>
                                    @php
                                        if (!isset($user)) {
                                            $user = \Illuminate\Support\Facades\Auth::user();
                                        }
                                    @endphp
                                    @if($user->isDeliveryUnit())
                                        <a class="text-amber-600 hover:text-amber-700 tooltip edit-deliverable" data-theme="dark" title="Edit Deliverable" href="javascript:;" data-tw-toggle="modal" data-tw-target="#edit-deliverable-modal" data-id="{{$deliverable->id}}" data-deliverable="{{$deliverable->deliverable}}" data-start-date="{{$deliverable->start_date ? date('Y-m-d', strtotime($deliverable->start_date)) : ''}}" data-end-date="{{$deliverable->end_date ? date('Y-m-d', strtotime($deliverable->end_date)) : ''}}" data-status="{{$deliverable->status}}">
                                            <span class="material-symbols-outlined text-[20px]">edit</span>
                                        </a>
                                        <a class="text-red-600 hover:text-red-700 tooltip" data-theme="dark" title="Delete Deliverable" href="javascript:;" data-tw-toggle="modal" data-tw-target="#delete-modal-preview{{ $deliverable->id }}">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </a>
                                    @endif
                                </div>
                                <div id="delete-modal-preview{{$deliverable->id}}" class="modal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-body p-0">
                                                <div class="p-5 text-center">
                                                    <span class="material-symbols-outlined text-red-600 text-6xl">error</span>
                                                    <div class="text-3xl mt-5">Are you sure?</div>
                                                    <div class="text-slate-500 mt-2">Do you really want to delete this Deliverable? <br>
                                                        <strong>{{$deliverable->deliverable}}</strong>
                                                    </div>
                                                </div>
                                                <div class="px-5 pb-8 text-center">
                                                    <button type="button" data-tw-dismiss="modal" class="btn btn-outline-secondary w-24 mr-1">Cancel</button>
                                                    <a href="{{ route('deliverables.delete',[$deliverable->id]) }}" class="btn btn-danger w-24">Delete</a>
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
        @else
            <div class="bg-white rounded-xl border border-primary/5 shadow-sm p-12 text-center">
                <span class="material-symbols-outlined text-6xl text-slate-300 mb-4">inventory_2</span>
                <p class="text-slate-600 font-medium">No deliverables found</p>
                <p class="text-sm text-slate-400 mt-2">Click <strong class="text-primary">Add Deliverable</strong> to add deliverables.</p>
            </div>
        @endif

        <!-- Footer Help/Actions -->
        <div class="mt-10 flex flex-col md:flex-row justify-between items-center p-6 rounded-xl border border-dashed border-slate-300 gap-4">
            <div class="flex items-center gap-4">
                <div class="size-10 rounded-full bg-primary/20 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined">help_center</span>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-900">Need help with reporting?</h4>
                    <p class="text-xs text-slate-500">Contact the PDCU Support desk for guidelines on deliverable updates.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Deliverable Modal -->
    <div id="header-footer-modal-preview" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{route('deliverable.save')}}" method="post">
                    @csrf
                    <input type="hidden" name="commitment_id" value="{{$commitment->id}}">
                    <div class="modal-header">
                        <h2 class="font-medium text-base mr-auto">Add Deliverable to {{$commitment->title(50)}}</h2>
                    </div>
                    <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                        <div class="col-span-6 sm:col-span-6">
                            <label for="modal-form-1" class="form-label">Deliverable</label>
                            <input id="modal-form-1" type="text" class="form-control" name="deliverable" required>
                        </div>
                        <div class="col-span-6 sm:col-span-6">
                            <label for="modal-form-start-date" class="form-label">Start Date</label>
                            <input id="modal-form-start-date" type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="col-span-6 sm:col-span-6">
                            <label for="modal-form-end-date" class="form-label">End Date</label>
                            <input id="modal-form-end-date" type="date" class="form-control" name="end_date" required>
                        </div>
                        <div class="col-span-6 sm:col-span-6">
                            <label for="modal-form-status" class="form-label">Status</label>
                            <select id="modal-form-status" class="form-control" name="status" required>
                                <option value="">Select</option>
                                <option value="Not Started">Not Started</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="At Risk">At Risk</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-tw-dismiss="modal" class="btn btn-outline-secondary w-20 mr-1">Cancel</button>
                        <button type="submit" class="btn btn-primary w-20">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Deliverable Modal -->
    <div id="edit-deliverable-modal" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{route('deliverable.update')}}" method="post">
                    @csrf
                    <input type="hidden" name="deliverable_id" id="edit-deliverable-id">
                    <div class="modal-header">
                        <h2 class="font-medium text-base mr-auto">Edit Deliverable</h2>
                    </div>
                    <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                        <div class="col-span-6 sm:col-span-6">
                            <label for="edit-deliverable-title" class="form-label">Deliverable</label>
                            <input id="edit-deliverable-title" type="text" class="form-control" name="deliverable" required>
                        </div>
                        <div class="col-span-6 sm:col-span-6">
                            <label for="edit-deliverable-start-date" class="form-label">Start Date</label>
                            <input id="edit-deliverable-start-date" type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="col-span-6 sm:col-span-6">
                            <label for="edit-deliverable-end-date" class="form-label">End Date</label>
                            <input id="edit-deliverable-end-date" type="date" class="form-control" name="end_date" required>
                        </div>
                        <div class="col-span-6 sm:col-span-6">
                            <label for="edit-deliverable-status" class="form-label">Status</label>
                            <select id="edit-deliverable-status" class="form-control" name="status" required>
                                <option value="">Select</option>
                                <option value="Not Started">Not Started</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="At Risk">At Risk</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-tw-dismiss="modal" class="btn btn-outline-secondary w-20 mr-1">Cancel</button>
                        <button type="submit" class="btn btn-primary w-20">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script>
        $(function () {
            // Search functionality
            $('#searchInput').on('keyup', function() {
                const value = $(this).val().toLowerCase();
                $('#deliverablesTable tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });

            // Status filter functionality
            $('#statusFilter').on('change', function() {
                const value = $(this).val().toLowerCase();
                $('#deliverablesTable tbody tr').filter(function() {
                    if (!value) {
                        $(this).show();
                        return;
                    }
                    const statusText = $(this).find('td:eq(1)').text().toLowerCase();
                    $(this).toggle(statusText.includes(value));
                });
            });

            // Edit deliverable functionality
            $('.edit-deliverable').on('click', function () {
                var deliverableId = $(this).data('id');
                var deliverableTitle = $(this).data('deliverable');
                var deliverableStartDate = $(this).data('start-date');
                var deliverableEndDate = $(this).data('end-date');
                var deliverableStatus = $(this).data('status');

                $('#edit-deliverable-id').val(deliverableId);
                $('#edit-deliverable-title').val(deliverableTitle);
                $('#edit-deliverable-start-date').val(deliverableStartDate);
                $('#edit-deliverable-end-date').val(deliverableEndDate);
                $('#edit-deliverable-status').val(deliverableStatus);
            });
        });
    </script>
@endsection
