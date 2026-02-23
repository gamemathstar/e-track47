@extends('layouts.app')

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&amp;display=swap"
          rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700,0..1&amp;display=swap"
          rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#008550",
                        "background-light": "#f5f8f7",
                        "background-dark": "#0f231b",
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
    <style>
        .material-icons, .material-symbols-outlined {
            font-family: 'Material Icons', 'Material Symbols Outlined';
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
    </style>
@endsection

@php
    use Carbon\Carbon;
@endphp

@section('content')
    <div class="p-8 space-y-6 bg-background-light">
        <!-- Title and Summary Section -->
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="space-y-1">
                <h1 class="text-3xl font-black tracking-tight text-slate-900">Data Entry Window Management</h1>
                <p class="text-slate-500">Configure submission windows and sector-specific overrides for quarterly
                    reporting.</p>
            </div>
            <div class="flex gap-3">
                <button id="lockAllBtn"
                        class="flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg font-bold text-sm hover:bg-red-100 hover:text-red-700 transition-all border border-transparent">
                    <span class="material-symbols-outlined text-lg">lock</span>
                    Global Lock All
                </button>
                <button id="unlockAllBtn"
                        class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-bold text-sm hover:bg-primary/90 shadow-lg shadow-primary/20 transition-all">
                    <span class="material-symbols-outlined text-lg">lock_open</span>
                    Global Unlock All
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-xl border border-primary/10 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Total Sectors</p>
                    <h3 class="text-2xl font-bold">{{ $totalSectors }}</h3>
                    <span
                        class="text-xs font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded-full mt-2 inline-block">Tracking Live</span>
                </div>
                <div class="size-12 rounded-lg bg-slate-50 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-3xl">domain</span>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl border border-primary/10 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Currently Open</p>
                    <h3 class="text-2xl font-bold">{{ $openCount }}</h3>
                    <span
                        class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full mt-2 inline-block">Active Window</span>
                </div>
                <div class="size-12 rounded-lg bg-slate-50 flex items-center justify-center text-amber-600">
                    <span class="material-symbols-outlined text-3xl">pending_actions</span>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl border border-primary/10 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Submission Rate</p>
                    <h3 class="text-2xl font-bold">{{ $openCount > 0 ? round(($openCount / $totalSectors) * 100, 1) : 0 }}
                        %</h3>
                    <span
                        class="text-xs font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded-full mt-2 inline-block">Q{{ $quarter }} {{ $year }}</span>
                </div>
                <div class="size-12 rounded-lg bg-slate-50 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-3xl">trending_up</span>
                </div>
            </div>
        </div>

        <!-- Grid and Control Table -->
        <div class="bg-white rounded-xl border border-primary/10 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-primary/10 flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <h3 class="font-bold text-lg">Sector Status Overview</h3>
                    <div class="flex gap-2">
                        <span
                            class="flex items-center gap-1 text-[10px] font-bold uppercase text-slate-400 bg-slate-100 px-2 py-1 rounded">
                            <span class="size-1.5 rounded-full bg-primary"></span> Open
                        </span>
                        <span
                            class="flex items-center gap-1 text-[10px] font-bold uppercase text-slate-400 bg-slate-100 px-2 py-1 rounded">
                            <span class="size-1.5 rounded-full bg-slate-400"></span> Locked
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <form method="GET" action="{{ route('data-entry.index') }}" id="filterForm" class="flex items-center gap-3">
                        <select name="year" id="filter_year"
                                class="form-select text-xs font-bold rounded-lg border-primary/10 bg-slate-50 focus:ring-primary focus:border-primary w-24">
                            @foreach(range(date('Y'), 2020) as $yr)
                                <option value="{{ $yr }}" {{ $year == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                        <select name="quarter" id="filter_quarter"
                                class="form-select text-xs font-bold rounded-lg border-primary/10 bg-slate-50 focus:ring-primary focus:border-primary w-20">
                            <option value="1" {{ $quarter == 1 ? 'selected' : '' }}>Q1</option>
                            <option value="2" {{ $quarter == 2 ? 'selected' : '' }}>Q2</option>
                            <option value="3" {{ $quarter == 3 ? 'selected' : '' }}>Q3</option>
                            <option value="4" {{ $quarter == 4 ? 'selected' : '' }}>Q4</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">MDA / Sector
                            Name
                        </th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Active Quarter
                        </th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Deadline</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Last Action</th>
{{--                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-right">--}}
{{--                            Actions--}}
{{--                        </th>--}}
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-primary/5">
                    @foreach($accessRecords as $record)
                        @php
                            $sector = is_object($record) && isset($record->sector) ? $record->sector : (is_object($record) ? $record : null);
                            $deadline = is_object($record) && isset($record->override_deadline) && $record->override_deadline ? $record->override_deadline : (is_object($record) && isset($record->deadline_date) ? $record->deadline_date : null);
                            $status = is_object($record) && isset($record->status) ? $record->status : 'closed';
                            $isOpen = is_object($record) && isset($record->status) && $record->status !== 'closed' && $deadline && Carbon::parse($deadline)->gte(Carbon::now());
                            $grantedBy = is_object($record) && isset($record->grantedBy) ? $record->grantedBy : null;
                            $grantedAt = is_object($record) && isset($record->granted_at) ? $record->granted_at : null;
                            $overrideReason = is_object($record) && isset($record->override_reason) ? $record->override_reason : null;
                        @endphp
                        @if($sector)
                            <tr class="hover:bg-primary/5 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-sm">{{ $sector->sector_name }}</span>
                                        <span class="text-xs text-slate-500">Sector ID: {{ $sector->id }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($isOpen)
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-primary/10 text-primary">
                                            <span class="size-1.5 rounded-full bg-primary"></span> Open
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500">
                                            <span class="size-1.5 rounded-full bg-slate-400"></span> Locked
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm font-medium">Q{{ $quarter }} {{ $year }}</td>
                                <td class="px-6 py-4 text-sm font-medium">
                                    @if($deadline)
                                        {{ Carbon::parse($deadline)->format('M d, Y') }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        @if($grantedBy && $grantedAt)
                                            <span
                                                class="text-xs font-medium text-amber-600 font-bold">Manual Override</span>
                                            <span class="text-[10px] text-slate-400 text-amber-600">By {{ $grantedBy->full_name ?? $grantedBy->name ?? 'N/A' }} • {{ Carbon::parse($grantedAt)->diffForHumans() }}</span>
                                        @elseif($isOpen)
                                            <span class="text-xs font-medium">System Auto-Unlock</span>
                                            <span
                                                class="text-[10px] text-slate-400">{{ Carbon::now()->diffForHumans() }}</span>
                                        @else
                                            <span class="text-xs font-medium">Window Expired</span>
                                            <span
                                                class="text-[10px] text-slate-400">{{ $deadline ? Carbon::parse($deadline)->diffForHumans() : 'N/A' }}</span>
                                        @endif
                                    </div>
                                </td>
{{--                                <td class="px-6 py-4 text-right">--}}
{{--                                    <button--}}
{{--                                        onclick="openOverrideModal({{ $sector->id }}, '{{ $sector->sector_name }}', {{ $quarter }}, {{ $year }})"--}}
{{--                                        class="text-primary hover:text-primary/70 text-sm font-bold underline underline-offset-4">--}}
{{--                                        Override--}}
{{--                                    </button>--}}
{{--                                </td>--}}
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Lower Section: Override Modal & Log Feed -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Specific Sector Override Form -->
            <div class="lg:col-span-1 bg-white p-6 rounded-xl border-l-4 border-l-primary shadow-sm space-y-4">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">edit_calendar</span>
                    <h3 class="font-bold">Manual Override Panel</h3>
                </div>
                <p class="text-xs text-slate-500">Select a sector to manually open their submission window.</p>
                <form id="overrideForm" class="space-y-4 pt-2">
                    <input type="hidden" name="sector_id" id="override_sector_id">
                    <input type="hidden" name="year" id="override_year" value="{{ $year }}">
                    <input type="hidden" name="quarter" id="override_quarter" value="{{ $quarter }}">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Sector to
                            Override</label>
                        <select name="sector_id" id="override_sector_select"
                                class="w-full form-select text-sm rounded-lg border-primary/10 focus:ring-primary focus:border-primary"
                                required>
                            <option value="">Select MDA...</option>
                            @foreach($sectors as $sector)
                                <option value="{{ $sector->id }}">{{ $sector->sector_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label
                                class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Quarter</label>
                            <select name="quarter" id="override_quarter_select"
                                    class="w-full form-select text-sm rounded-lg border-primary/10 focus:ring-primary focus:border-primary"
                                    required>
                                <option value="1" {{ $quarter == 1 ? 'selected' : '' }}>Q1</option>
                                <option value="2" {{ $quarter == 2 ? 'selected' : '' }}>Q2</option>
                                <option value="3" {{ $quarter == 3 ? 'selected' : '' }}>Q3</option>
                                <option value="4" {{ $quarter == 4 ? 'selected' : '' }}>Q4</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-wider">New
                                Deadline</label>
                            <input type="date" name="override_deadline" id="override_deadline"
                                   class="w-full form-input text-sm rounded-lg border-primary/10 focus:ring-primary focus:border-primary"
                                   required>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Reason for Opening
                            (Mandatory)</label>
                        <textarea name="override_reason" id="override_reason"
                                  class="w-full form-textarea text-sm rounded-lg border-primary/10 focus:ring-primary focus:border-primary"
                                  placeholder="e.g., Late submission request approved by DG..." rows="3"
                                  required></textarea>
                    </div>
                    <button type="submit"
                            class="w-full py-2.5 bg-primary text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/20 flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-lg">save</span>
                        Update & Unlock Window
                    </button>
                </form>
            </div>
            <!-- Data Entry Audit Log -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl border border-primary/10 shadow-sm flex flex-col">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-slate-400">history</span>
                        <h3 class="font-bold">Administrative Access Log</h3>
                    </div>
                </div>
                <div class="flex-1 space-y-4">
                    @forelse($accessLog as $log)
                        <div
                            class="flex gap-4 items-start p-3 rounded-lg hover:bg-slate-50 transition-colors border border-transparent hover:border-primary/10">
                            <div
                                class="size-8 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-lg">verified_user</span>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-bold">
                                            {{ $log->grantedBy->full_name ?? $log->grantedBy->name ?? 'System' }}
                                            <span class="font-normal text-slate-500">manually opened window for</span>
                                            {{ $log->sector->sector_name ?? 'Unknown Sector' }}
                                        </p>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            <span class="font-semibold">Q{{ $log->quarter ?? 'N/A' }} {{ $log->year ?? 'N/A' }}</span>
                                        </p>
                                    </div>
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase">{{ $log->granted_at ? Carbon::parse($log->granted_at)->diffForHumans() : 'N/A' }}</span>
                                </div>
                                <p class="text-xs text-slate-600 mt-1 italic">
                                    "{{ $log->override_reason ?? 'No reason provided' }}"</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 text-center py-8">No access log entries yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        const lockAllBtn = document.getElementById('lockAllBtn');
        const unlockAllBtn = document.getElementById('unlockAllBtn');
        const overrideForm = document.getElementById('overrideForm');

        // Lock All
        lockAllBtn.addEventListener('click', function () {
            if (confirm('Are you sure you want to lock all sectors for Q{{ $quarter }} {{ $year }}?')) {
                fetch('{{ route('data-entry.lock-all') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        year: {{ $year }},
                        quarter: {{ $quarter }}
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert(data.message || 'Failed to lock all sectors');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while locking sectors');
                    });
            }
        });

        // Unlock All
        unlockAllBtn.addEventListener('click', function () {
            if (confirm('Are you sure you want to unlock all sectors for Q{{ $quarter }} {{ $year }}?')) {
                fetch('{{ route('data-entry.unlock-all') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        year: {{ $year }},
                        quarter: {{ $quarter }}
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert(data.message || 'Failed to unlock all sectors');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while unlocking sectors');
                    });
            }
        });

        // Override Form
        overrideForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Get values directly from form elements to avoid conflicts with duplicate names
            const sectorSelect = document.getElementById('override_sector_select');
            const sectorId = sectorSelect ? sectorSelect.value : '';

            if (!sectorId) {
                alert('Please select a sector.');
                return;
            }

            const formData = new FormData(this);
            const data = {
                sector_id: sectorId, // Use the select value explicitly
                year: formData.get('year') || document.getElementById('override_year').value,
                quarter: formData.get('quarter') || document.getElementById('override_quarter_select').value,
                override_deadline: formData.get('override_deadline'),
                override_reason: formData.get('override_reason')
            };

            fetch('{{ route('data-entry.grant-override') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
                .then(response => {
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        // If not JSON, return as text
                        return response.text().then(text => {
                            throw new Error('Server returned non-JSON response. Please check your inputs.');
                        });
                    }
                })
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        // Handle validation errors
                        let errorMessage = data.message || 'Failed to grant override';
                        if (data.errors) {
                            const errorList = Object.values(data.errors).flat().join('\n');
                            errorMessage = errorMessage + '\n\n' + errorList;
                        }
                        alert(errorMessage);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(error.message || 'An error occurred while granting override. Please check your inputs and try again.');
                });
        });

        // Sync hidden field when select changes
        const sectorSelect = document.getElementById('override_sector_select');
        if (sectorSelect) {
            sectorSelect.addEventListener('change', function() {
                document.getElementById('override_sector_id').value = this.value;
            });
        }

        // Filter form - auto-submit on year/quarter change to reload statistics
        const filterForm = document.getElementById('filterForm');
        const filterYear = document.getElementById('filter_year');
        const filterQuarter = document.getElementById('filter_quarter');

        if (filterYear) {
            filterYear.addEventListener('change', function() {
                filterForm.submit();
            });
        }

        if (filterQuarter) {
            filterQuarter.addEventListener('change', function() {
                filterForm.submit();
            });
        }

        function openOverrideModal(sectorId, sectorName, quarter, year) {
            document.getElementById('override_sector_id').value = sectorId;
            document.getElementById('override_sector_select').value = sectorId;
            document.getElementById('override_year').value = year;
            document.getElementById('override_quarter').value = quarter;
            document.getElementById('override_quarter_select').value = quarter;

            // Set minimum date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('override_deadline').min = tomorrow.toISOString().split('T')[0];
        }
    </script>
@endsection
