@php use Carbon\Carbon; @endphp
@extends('layouts.app')

@section('css')
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700;800;900&display=swap"
          rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
          rel="stylesheet"/>
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
        $totalCommitments = $commitments->count();
        $completedCommitments = $commitments->where('status', 'Completed')->count();
        $inProgressCommitments = $commitments->where('status', 'In Progress')->count();
        $atRiskCommitments = $commitments->where('status', 'At Risk')->count();
        $notStartedCommitments = $commitments->where('status', 'Not Started')->count();

        // Get current year and quarter for filtering (from controller or request)
        $currentYear = $year ?? request('year', date('Y'));
        $currentQuarter = $quarter ?? request('quarter', null);
    @endphp

    <div class="p-8 space-y-6">
        <!-- Sector Info Card -->
        <div class="bg-white p-5 rounded-xl border border-primary/5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">{{ $sector->sector_name }}</h2>
                    <p class="text-sm text-slate-600 mt-2">{{ $sector->description }}</p>
                </div>
                <div class="flex items-center gap-3">
                    @if($user->isSectorHead())
                        @php
                            // Count pending approvals for this sector, filtered by year and quarter
                            $pendingCount = 0;
                            $pendingRecords = collect();
                            if ($sector = $user->isSectorHead()) {
                                // Query for pending records:
                                // - Not approved by sector head yet
                                // - Has actual_value (Data Admin has filled it)
                                // - Matches selected year and quarter
                               $query = \App\Models\PerformanceTracking::query()
                                    ->whereHas('kpi.deliverable.commitment', function ($q) use ($sector) {
                                        $q->where('sector_id', $sector->id);
                                    })
                                    ->whereNull('sector_head_approved_by')
                                    ->whereNotNull('actual_value')
                                    ->where('actual_value', '!=', 0)
                                    ->where('year', $currentYear);

                                if ($currentQuarter) {
                                    $query->where('quarter', $currentQuarter);
                                }

                                $pendingRecords = $query->distinct()->get();
                                $pendingCount = $pendingRecords->count();
                            }
                        @endphp
                        <div class="flex items-center gap-2">
                            <select id="approve-year-select" class="form-control text-sm" style="width: 100px;">
                                @foreach(range(2024, date('Y')) as $yr)
                                    <option
                                        value="{{ $yr }}" {{ $currentYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                                @endforeach
                            </select>
                            <select id="approve-quarter-select" class="form-control text-sm" style="width: 120px;">
                                <option value="">All Quarters</option>
                                <option value="1" {{ $currentQuarter == 1 ? 'selected' : '' }}>Q1</option>
                                <option value="2" {{ $currentQuarter == 2 ? 'selected' : '' }}>Q2</option>
                                <option value="3" {{ $currentQuarter == 3 ? 'selected' : '' }}>Q3</option>
                                <option value="4" {{ $currentQuarter == 4 ? 'selected' : '' }}>Q4</option>
                            </select>
                        </div>
                        <button
                            id="approve-all-data-btn"
                            class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 transition-all shadow-md shadow-emerald-500/20"
                            data-sector-id="{{ $sector->id }}"
                            data-year="{{ $currentYear }}"
                            data-quarter="{{ $currentQuarter ?? '' }}"
                            @if($pendingCount == 0) disabled @endif>
                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                            Approve Data
                            @if($pendingCount > 0)
                                <span
                                    class="bg-white text-emerald-500 rounded-full px-2 py-0.5 text-xs font-bold">{{ $pendingCount }}</span>
                            @endif
                        </button>
                    @endif
                    <button
                        class="bg-primary/10 hover:bg-primary/20 text-primary px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 transition-all"
                        data-tw-toggle="modal" data-tw-target="#sectorHeadModal">
                        <span class="material-symbols-outlined text-[18px]">person</span>
                        MDA/Sector Head
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-xl border border-primary/5 shadow-sm">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Commitments</span>
                    <div class="p-2 bg-primary/10 text-primary rounded-lg">
                        <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                    </div>
                </div>
                <p class="text-2xl font-black text-slate-900">{{ $totalCommitments }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-primary/5 shadow-sm">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Completed</span>
                    <div class="p-2 bg-emerald-100 text-emerald-600 rounded-lg">
                        <span class="material-symbols-outlined text-[20px]">task_alt</span>
                    </div>
                </div>
                <p class="text-2xl font-black text-slate-900">{{ $completedCommitments }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-primary/5 shadow-sm">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">In Progress</span>
                    <div class="p-2 bg-amber-100 text-amber-600 rounded-lg">
                        <span class="material-symbols-outlined text-[20px]">pending_actions</span>
                    </div>
                </div>
                <p class="text-2xl font-black text-slate-900">{{ $inProgressCommitments }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-primary/5 shadow-sm">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">At Risk</span>
                    <div class="p-2 bg-red-100 text-red-600 rounded-lg">
                        <span class="material-symbols-outlined text-[20px]">report</span>
                    </div>
                </div>
                <p class="text-2xl font-black text-slate-900">{{ $atRiskCommitments }}</p>
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

        <!-- Table Controls -->
        <div
            class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-primary/5 shadow-sm">
            <div class="flex flex-1 items-center gap-3">
                <div class="relative w-full max-w-sm">
                    <span
                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                    <input
                        class="w-full bg-slate-50 border-none rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-1 focus:ring-primary"
                        placeholder="Filter commitments..." type="text" id="searchInput"/>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @php
                    if (!isset($user)) {
                        $user = \Illuminate\Support\Facades\Auth::user();
                    }
                @endphp
                @if($user->isDeliveryUnit())
                    <button
                        class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 transition-all"
                        data-tw-toggle="modal" data-tw-target="#header-footer-modal-preview">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        New Commitment
                    </button>
                @endif
            </div>
        </div>

        <!-- Data Table -->
        @if($commitments->count())
            <div class="bg-white rounded-xl border border-primary/5 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="commitmentsTable">
                        <thead>
                        <tr class="border-b border-slate-100">
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Commitment
                                Name
                            </th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Current
                                Status
                            </th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                        @foreach($commitments as $commitment)
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-6 py-4">
                                    <span class="text-sm font-medium text-slate-600">{{ $loop->iteration }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-sm font-bold text-slate-900 group-hover:text-primary transition-colors cursor-pointer">{{ $commitment->title(48) }}</span>
                                        <span
                                            class="text-xs text-slate-400 mt-0.5 font-medium">Type: {{ $commitment->type ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($commitment->status == 'Completed')
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-700 uppercase tracking-wide">Completed</span>
                                    @elseif($commitment->status == 'In Progress')
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-700 uppercase tracking-wide">In Progress</span>
                                    @elseif($commitment->status == 'At Risk')
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-red-100 text-red-700 uppercase tracking-wide">At Risk</span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 uppercase tracking-wide">Not Started</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        if (!isset($user)) {
                                            $user = \Illuminate\Support\Facades\Auth::user();
                                        }
                                    @endphp
                                    <div class="flex items-center gap-2">
                                        @if($user->isDeliveryUnit())
                                            <a class="flex items-center text-amber-600 hover:text-amber-700 tooltip edit"
                                               data-theme="dark" title="Edit Commitment" href="javascript:;"
                                               data-tw-toggle="modal" data-tw-target="#edit-photo"
                                               data-id="{{$commitment->id}}" data-name="{{$commitment->name}}"
                                               data-type="{{$commitment->type}}"
                                               data-description="{{ htmlspecialchars($commitment->description, ENT_QUOTES, 'UTF-8') }}"
                                               data-status="{{$commitment->status}}"
                                               data-photo="{{ secure_asset(( is_null($commitment->img_url)? 'dist/images/preview-3.jpg':'uploads/'.$commitment->img_url)) }}">
                                                <span class="material-symbols-outlined text-[20px]">edit</span>
                                            </a>
                                        @endif
                                        <a class="flex items-center text-primary hover:text-primary/80 tooltip"
                                           data-theme="dark" title="View Commitment"
                                           href="{{route('commitments.deliverables',[$commitment->id])}}">
                                            <span class="material-symbols-outlined text-[20px]">visibility</span>
                                        </a>
                                        @if($user->isDeliveryUnit())
                                            <a class="flex items-center text-red-600 hover:text-red-700 tooltip"
                                               data-theme="dark" title="Delete Commitment" href="javascript:;"
                                               data-tw-toggle="modal"
                                               data-tw-target="#delete-modal-preview{{$commitment->id}}">
                                                <span class="material-symbols-outlined text-[20px]">delete</span>
                                            </a>
                                        @endif
                                    </div>
                                    <div id="delete-modal-preview{{$commitment->id}}" class="modal" tabindex="-1"
                                         aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-body p-0">
                                                    <div class="p-5 text-center">
                                                        <span class="material-symbols-outlined text-red-600 text-6xl">error</span>
                                                        <div class="text-3xl mt-5">Are you sure?</div>
                                                        <div class="text-slate-500 mt-2">Do you really want to delete
                                                            this Commitment? <br>
                                                            <strong>{{$commitment->title(48)}}</strong>
                                                        </div>
                                                    </div>
                                                    <div class="px-5 pb-8 text-center">
                                                        <button type="button" data-tw-dismiss="modal"
                                                                class="btn btn-outline-secondary w-24 mr-1">Cancel
                                                        </button>
                                                        <a href="{{ route('commitments.delete',[$commitment->id]) }}"
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
            </div>
        @else
            <div class="bg-white rounded-xl border border-primary/5 shadow-sm p-12 text-center">
                <span class="material-symbols-outlined text-6xl text-slate-300 mb-4">inventory_2</span>
                <p class="text-slate-600 font-medium">No commitments found</p>
                <p class="text-sm text-slate-400 mt-2">Click <strong class="text-primary">New Commitment</strong> to add
                    commitments.</p>
            </div>
        @endif
    </div>

    <!-- Edit Commitment Modal -->
    <div id="edit-photo" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{route('commitments.update')}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="commitment_id" id="commitmentId">
                    <div class="modal-header">
                        <h2 class="font-medium text-base mr-auto">Edit Commitment</h2>
                    </div>
                    <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                        <div class="col-span-6 sm:col-span-6">
                            <label for="edit-commitment-title" class="form-label">Commitment Title</label>
                            <input id="edit-commitment-title" type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-span-6 sm:col-span-6">
                            <label for="edit-commitment-type" class="form-label">Commitment Type</label>
                            <input id="edit-commitment-type" type="text" class="form-control" name="type" required>
                        </div>
                        <div class="col-span-6 sm:col-span-6">
                            <label for="edit-commitment-description" class="form-label">Description</label>
                            <textarea name="description" id="edit-commitment-description" class="form-control"
                                      required></textarea>
                        </div>
                        <div class="col-span-6 sm:col-span-6">
                            <label for="edit-commitment-status" class="form-label">Status</label>
                            <select id="edit-commitment-status" class="form-control" name="status" required>
                                <option value="">Select</option>
                                <option value="Not Started">Not Started</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="At Risk">At Risk</option>
                            </select>
                        </div>
                        <div class="col-span-12 sm:col-span-12">
                            <label for="edit-commitment-photo" class="form-label">Current Photo</label>
                            <div class="h-40 2xl:h-56 image-fit mb-3">
                                <img class="rounded-md" id="commitmentPhoto"/>
                            </div>
                            <input type="file" name="img_url" id="edit-commitment-photo" class="form-control">
                            <small class="text-muted">Leave empty to keep current photo</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-tw-dismiss="modal" class="btn btn-outline-secondary w-20 mr-1">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary w-20">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Commitment Modal -->
    <div id="header-footer-modal-preview" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{route('commitments.save')}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="sector_id" value="{{$sector->id}}">
                    <div class="modal-header">
                        <h2 class="font-medium text-base mr-auto">Add Commitment to {{$sector->sector_name}}</h2>
                    </div>
                    <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                        <div class="col-span-6 sm:col-span-6">
                            <label for="modal-form-1" class="form-label">Commitment Title</label>
                            <input id="modal-form-1" type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-span-6 sm:col-span-6">
                            <label for="modal-form-type" class="form-label">Commitment Type</label>
                            <input id="modal-form-type" type="text" class="form-control" name="type" required>
                        </div>
                        <div class="col-span-6 sm:col-span-6">
                            <label for="modal-form-2" class="form-label">Description</label>
                            <textarea name="description" id="modal-form-2" class="form-control" required></textarea>
                        </div>
                        <div class="col-span-6 sm:col-span-6">
                            <label for="modal-form-picture" class="form-label">Picture</label>
                            <input type="file" name="img_url" id="modal-form-picture" class="form-control">
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
                        <button type="button" data-tw-dismiss="modal" class="btn btn-outline-secondary w-20 mr-1">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary w-20">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function () {
            url = "{{route('sectors.view',['id'=>$sector->id])}}/";


            // Search functionality
            $('#searchInput').on('keyup', function () {
                const value = $(this).val().toLowerCase();
                $('#commitmentsTable tbody tr').filter(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });

            $('.edit').on('click', function () {
                let commitmentId = $(this).data('id');
                let commitmentName = $(this).data('name');
                let commitmentType = $(this).data('type');
                let commitmentDescription = $(this).data('description');
                let commitmentStatus = $(this).data('status');
                let commitmentPhoto = $(this).data('photo');

                $('#commitmentId').val(commitmentId);
                $('#edit-commitment-title').val(commitmentName);
                $('#edit-commitment-type').val(commitmentType);
                $('#edit-commitment-description').val(commitmentDescription);
                $('#edit-commitment-status').val(commitmentStatus);
                $('#commitmentPhoto').attr('src', commitmentPhoto);
            });
        });

        function loadCommitments(id) {
            $.ajax({
                type: 'Post',
                url: "{{route("commitments.deliverables",[''])}}/" + id,
                data: {_token: '{{ csrf_token() }}'},
                success: function (data) {
                    $("#loadArea").html(data);
                }
            });
        }

        // Handle year and quarter selector changes - reload page with filters
        $('#approve-year-select, #approve-quarter-select').on('change', function () {
            var year = $('#approve-year-select').val();
            var quarter = $('#approve-quarter-select').val();
            var url = new URL(window.location.href);
            url.searchParams.set('year', year);
            if (quarter) {
                url.searchParams.set('quarter', quarter);
            } else {
                url.searchParams.delete('quarter');
            }
            window.location.href = url.toString();
        });

        // Handle Approve Data button click - approve all pending records
        $('#approve-all-data-btn').on('click', function (e) {
            e.preventDefault();

            // Check if button is disabled
            if ($(this).prop('disabled')) {
                return false;
            }

            var button = $(this);
            var year = $('#approve-year-select').val();
            var quarter = $('#approve-quarter-select').val();
            var sectorId = button.data('sector-id');
            var originalText = button.html();

            // Build confirmation message
            var periodText = year + (quarter ? ' Q' + quarter : ' (All Quarters)');
            var confirmMessage = 'Are you sure you want to approve all pending performance tracking records for ' + periodText + '?<br><br><strong>This action cannot be undone.</strong>';

            // Show SweetAlert confirmation modal
            Swal.fire({
                icon: 'question',
                title: 'Approve Performance Tracking?',
                html: confirmMessage,
                showCancelButton: true,
                confirmButtonColor: '#10b981', // emerald-500
                cancelButtonColor: '#6b7280', // slate-500
                confirmButtonText: '<span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 4px;">check_circle</span> Yes, Approve',
                cancelButtonText: '<span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 4px;">cancel</span> Cancel',
                reverseButtons: true,
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // User confirmed - proceed with approval
                    button.prop('disabled', true).html('<span class="material-symbols-outlined text-[18px]">hourglass_empty</span> Approving...');

                    $.ajax({
                        url: '{{ route("performance.tracking.approve") }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            year: year,
                            quarter: quarter || null,
                            sector_id: sectorId
                        },
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        success: function (response) {
                            if (response.success) {
                                // Show success message using SweetAlert
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Approved!',
                                    html: '<div style="text-align: center;"><span class="material-symbols-outlined" style="font-size: 48px; color: #10b981; margin-bottom: 16px;">check_circle</span><br>' +
                                          (response.message || 'Performance tracking records approved successfully.') + '</div>',
                                    confirmButtonText: 'OK',
                                    confirmButtonColor: '#10b981',
                                    timer: 3000,
                                    timerProgressBar: true
                                }).then(function() {
                                    location.reload();
                                });
                            } else {
                                // Show error message using SweetAlert
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    html: '<div style="text-align: center;"><span class="material-symbols-outlined" style="font-size: 48px; color: #ef4444; margin-bottom: 16px;">error</span><br>' +
                                          (response.message || 'An error occurred while approving records.') + '</div>',
                                    confirmButtonText: 'OK',
                                    confirmButtonColor: '#ef4444'
                                });
                                button.prop('disabled', false).html(originalText);
                            }
                        },
                        error: function (xhr) {
                            var errorMsg = xhr.responseJSON?.message || 'An error occurred. Please try again.';
                            // Show error message using SweetAlert
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                html: '<div style="text-align: center;"><span class="material-symbols-outlined" style="font-size: 48px; color: #ef4444; margin-bottom: 16px;">error</span><br>' +
                                      errorMsg + '</div>',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#ef4444'
                            });
                            button.prop('disabled', false).html(originalText);
                        }
                    });
                }
                // If user cancelled, do nothing (button remains enabled)
            });
        });
    </script>
@endsection
