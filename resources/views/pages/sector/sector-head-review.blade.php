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
        $periodLabel = $year . ($quarter ? ' · Q' . $quarter : ' · All quarters');
        $backUrl = sector_view_url($sector->id) . '&year=' . $year . ($quarter ? '&quarter=' . $quarter : '');
    @endphp

    <div class="p-8 space-y-6 max-w-7xl mx-auto">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ $backUrl }}"
                   class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:text-primary/80 mb-2">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    Back to {{ $sector->sector_name }}
                </a>
                <h1 class="text-2xl font-bold text-slate-900">Review performance submissions</h1>
                <p class="text-sm text-slate-600 mt-1">{{ $periodLabel }} — select KPI rows to approve, then submit.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-lg border border-primary/20 bg-primary/5 px-4 py-3 text-sm text-slate-800">
                {{ session('success') }}
            </div>
        @endif
        @if(session('failure'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('failure') }}
            </div>
        @endif

        @if($pendingTrackings->isEmpty())
            <div class="bg-white rounded-xl border border-primary/10 shadow-sm p-10 text-center">
                <span class="material-symbols-outlined text-5xl text-slate-300">inbox</span>
                <p class="mt-4 text-slate-600 font-medium">No pending submissions for this period.</p>
                <a href="{{ $backUrl }}" class="mt-4 inline-block text-primary font-semibold text-sm hover:underline">Return
                    to sector</a>
            </div>
        @else
            <form id="sector-head-approval-form" method="POST" action="{{ route('performance.tracking.approve') }}"
                  class="bg-white rounded-xl border border-primary/10 shadow-sm overflow-hidden">
                @csrf
                <input type="hidden" name="approval_mode" value="selective">
                <input type="hidden" name="year" value="{{ $year }}">
                @if($quarter)
                    <input type="hidden" name="quarter" value="{{ $quarter }}">
                @endif

                <div
                    class="px-6 py-4 border-b border-primary/10 flex flex-wrap items-center justify-between gap-4 bg-slate-50/50">
                    <p class="text-xs text-slate-500">{{ $pendingTrackings->count() }} row(s) pending</p>
                    <div class="flex items-center gap-3">
                        <label for="select-all-approval" class="text-sm font-semibold text-slate-800 cursor-pointer">Select
                            all</label>
                        <input type="checkbox" id="select-all-approval"
                               class="rounded border-slate-300 text-primary focus:ring-primary/30 h-4 w-4">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                        <tr class="bg-slate-50/80 text-slate-600 text-xs uppercase tracking-wider font-bold border-b border-primary/10">
                            <th class="px-4 py-3">KPI</th>
                            <th class="px-4 py-3 hidden lg:table-cell">Commitment / deliverable</th>
                            <th class="px-4 py-3 text-center">Q</th>
                            <th class="px-4 py-3 text-right">Target</th>
                            <th class="px-4 py-3 text-right">Milestone</th>
                            <th class="px-4 py-3 text-right">Value (Data Admin)</th>
                            <th class="px-4 py-3 w-14 text-center">Include</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-primary/5">
                        @foreach($pendingTrackings as $tracking)
                            @php
                                $kpi = $tracking->kpi;
                                $targetRow = $kpi ? ($targetsByKpiId[$kpi->id] ?? null) : null;
                                $targetDisplay = $targetRow && $targetRow->target !== '' && $targetRow->target !== null
                                    ? $targetRow->target
                                    : '—';
                            @endphp
                            <tr class="hover:bg-primary/[0.02]">
                                <td class="px-4 py-3 align-top">
                                    <p class="font-semibold text-slate-900">{{ $kpi->kpi ?? 'KPI #' . $tracking->kpi_id }}</p>
                                    @if($kpi && $kpi->unit_of_measurement)
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $kpi->unit_of_measurement }}</p>
                                    @endif
                                    <div class="lg:hidden mt-2 text-xs text-slate-600">
                                        @if($kpi && $kpi->deliverable && $kpi->deliverable->commitment)
                                            <span
                                                class="block text-slate-500">{{ $kpi->deliverable->commitment->name ?? '' }}</span>
                                            <span>{{ $kpi->deliverable->deliverable ?? '' }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top hidden lg:table-cell text-slate-700">
                                    @if($kpi && $kpi->deliverable && $kpi->deliverable->commitment)
                                        <p class="font-medium text-slate-900">{{ $kpi->deliverable->commitment->name ?? '—' }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $kpi->deliverable->deliverable ?? '—' }}</p>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center align-top font-semibold text-slate-800">
                                    Q{{ $tracking->quarter }}</td>
                                <td class="px-4 py-3 text-right align-top text-slate-800">{{ $targetDisplay }}</td>
                                <td class="px-4 py-3 text-right align-top text-slate-800">{{ $tracking->milestone !== null && $tracking->milestone !== '' ? $tracking->milestone : '—' }}</td>
                                <td class="px-4 py-3 text-right align-top font-semibold text-primary">{{ $tracking->actual_value }}</td>
                                <td class="px-4 py-3 text-center">
                                    <input type="checkbox" name="tracking_ids[]" value="{{ $tracking->id }}"
                                           class="row-approval-cb rounded border-slate-300 text-primary focus:ring-primary/30 h-4 w-4">
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div
                    class="px-6 py-5 border-t border-primary/10 flex flex-wrap items-center justify-between gap-4 bg-slate-50/30">
                    <p class="text-xs text-slate-500 max-w-xl">Only checked rows are approved and sent to facilitators.
                        Unchecked rows stay pending.</p>
                    <button type="submit" id="approve-selected-btn" disabled
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 text-sm font-bold shadow-md shadow-emerald-600/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none">
                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                        Approve selected
                    </button>
                </div>
            </form>
        @endif
    </div>
@endsection

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script>
        $(function () {
            function syncApproveButton() {
                var n = $('.row-approval-cb:checked').length;
                $('#approve-selected-btn').prop('disabled', n < 1);
            }

            function syncSelectAll() {
                var total = $('.row-approval-cb').length;
                var checked = $('.row-approval-cb:checked').length;
                $('#select-all-approval').prop('checked', total > 0 && checked === total);
            }

            $('#select-all-approval').on('change', function () {
                $('.row-approval-cb').prop('checked', $(this).prop('checked'));
                syncApproveButton();
            });

            $('.row-approval-cb').on('change', function () {
                syncSelectAll();
                syncApproveButton();
            });

            syncApproveButton();

            $('#sector-head-approval-form').on('submit', function (e) {
                if ($('.row-approval-cb:checked').length === 0) {
                    e.preventDefault();
                    alert('Select at least one KPI row to approve.');
                    return false;
                }
            });
        });
    </script>
@endsection
