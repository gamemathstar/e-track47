@extends('layouts.app')

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            display: inline-block;
        }

        .bulk-upload-page {
            --bu-background-light: #f5f8f7;
            --bu-surface-bright: #f5fbf3;
            --bu-surface-lowest: #ffffff;
            --bu-surface-container-low: #f0f5ee;
            --bu-primary: #00693e;
            --bu-on-primary: #ffffff;
            --bu-on-surface-variant: #3e4a41;
            --bu-on-background: #171d19;
            --bu-secondary: #41664e;
            --bu-success: #10b981;
            --bu-warning: #f59e0b;
            background-color: var(--bu-background-light);
        }

        .bu-card { background-color: var(--bu-surface-lowest); }
        .bu-card--bright { background-color: var(--bu-surface-bright); }
        .bu-card--warning { background-color: rgba(245, 158, 11, 0.05); }
        .bu-btn-outline {
            color: var(--bu-primary);
            border: 1px solid rgba(0, 105, 62, 0.2);
        }
        .bu-btn-outline:hover { background-color: rgba(0, 105, 62, 0.05); }
        .bu-btn-primary {
            background-color: var(--bu-primary);
            color: var(--bu-on-primary);
        }
        .bu-btn-primary:hover { background-color: rgba(0, 105, 62, 0.9); }
        .bu-tab-active {
            border-color: var(--bu-primary);
            color: var(--bu-primary);
            font-weight: 700;
        }
    </style>
@endsection

@section('content')
    @php
        $summary = $preview['summary'];
        $warnings = $preview['warnings'] ?? [];
        $commitmentRows = collect($preview['commitments'] ?? [])->flatMap(function ($commitment) {
            return collect($commitment['rows'] ?? [])->map(function ($row) use ($commitment) {
                $row['responsible_unit'] = $commitment['responsible_unit'] ?? 'Sector Unit';
                $row['commitment_title'] = $commitment['title'] ?? '';

                return $row;
            });
        });
    @endphp

    <div class="bulk-upload-page p-4 md:p-8 font-display text-sm text-on-background min-h-full">
        <div class="max-w-6xl mx-auto space-y-8">
            <nav class="text-sm text-on-surface-variant flex items-center gap-2 flex-wrap">
                <a href="{{ route('bulk-upload.index') }}" class="hover:text-primary transition-colors">Bulk Uploads</a>
                <span class="material-symbols-outlined text-xs">chevron_right</span>
                <span class="font-semibold text-primary">Review &amp; Preview</span>
            </nav>

            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl md:text-[32px] font-bold tracking-tight text-on-background mb-2">
                        Review &amp; Preview
                    </h1>
                    <div class="flex flex-wrap items-center gap-3 text-on-surface-variant">
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">domain</span>
                            <span>Sector: <strong class="text-on-background">{{ $meta['sector_name'] }}</strong></span>
                        </div>
                        <span class="w-1 h-1 rounded-full bg-primary/30"></span>
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">calendar_month</span>
                            <span>Fiscal Year: <strong class="text-on-background">{{ $meta['framework_year'] }}</strong></span>
                        </div>
                        <span class="w-1 h-1 rounded-full bg-primary/30"></span>
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">description</span>
                            <span>File: <strong class="text-on-background">{{ $meta['file_name'] }}</strong></span>
                        </div>
                        @if(($uploadMode ?? 'structure') === 'actuals' && !empty($meta['reporting_quarter']))
                            <span class="w-1 h-1 rounded-full bg-primary/30"></span>
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[18px]">event</span>
                                <span>Quarter: <strong class="text-on-background">Q{{ $meta['reporting_quarter'] }}</strong></span>
                            </div>
                        @endif
                    </div>
                </div>
                <a href="{{ route('bulk-upload.index') }}"
                   class="bu-btn-outline px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 self-start">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    Back to Upload
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bu-card border border-primary/10 rounded-xl p-5 shadow-sm relative overflow-hidden">
                    <div class="p-2 bg-primary/10 rounded-lg text-primary inline-flex mb-4">
                        <span class="material-symbols-outlined">format_list_bulleted</span>
                    </div>
                    <p class="text-on-surface-variant text-sm mb-1">Total Records</p>
                    <p class="text-2xl font-semibold text-on-background">{{ $summary['total_records'] }}</p>
                </div>
                @if(($uploadMode ?? 'structure') === 'actuals')
                    <div class="bu-card border border-primary/10 rounded-xl p-5 shadow-sm relative overflow-hidden">
                        <div class="p-2 rounded-lg inline-flex mb-4" style="background: rgba(16,185,129,0.1); color: var(--bu-success);">
                            <span class="material-symbols-outlined">edit_note</span>
                        </div>
                        <p class="text-on-surface-variant text-sm mb-1">Ready to Update</p>
                        <p class="text-2xl font-semibold text-on-background">{{ $summary['actual_updates'] ?? 0 }}</p>
                    </div>
                    <div class="bu-card border border-primary/10 rounded-xl p-5 shadow-sm relative overflow-hidden">
                        <div class="p-2 rounded-lg inline-flex mb-4" style="background: rgba(65,102,78,0.1); color: var(--bu-secondary);">
                            <span class="material-symbols-outlined">handshake</span>
                        </div>
                        <p class="text-on-surface-variant text-sm mb-1">Commitments</p>
                        <p class="text-2xl font-semibold text-on-background">{{ $summary['commitments'] }}</p>
                    </div>
                @else
                    <div class="bu-card border border-primary/10 rounded-xl p-5 shadow-sm relative overflow-hidden">
                        <div class="p-2 rounded-lg inline-flex mb-4" style="background: rgba(65,102,78,0.1); color: var(--bu-secondary);">
                            <span class="material-symbols-outlined">handshake</span>
                        </div>
                        <p class="text-on-surface-variant text-sm mb-1">Commitments</p>
                        <p class="text-2xl font-semibold text-on-background">{{ $summary['commitments'] }}</p>
                    </div>
                    <div class="bu-card border border-primary/10 rounded-xl p-5 shadow-sm relative overflow-hidden">
                        <div class="p-2 rounded-lg inline-flex mb-4" style="background: rgba(16,185,129,0.1); color: var(--bu-success);">
                            <span class="material-symbols-outlined">inventory_2</span>
                        </div>
                        <p class="text-on-surface-variant text-sm mb-1">Deliverables</p>
                        <p class="text-2xl font-semibold text-on-background">{{ $summary['deliverables'] }}</p>
                    </div>
                @endif
                <div class="bu-card--warning border border-warning/20 rounded-xl p-5 shadow-sm relative overflow-hidden">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-2 rounded-lg inline-flex" style="background: rgba(245,158,11,0.2); color: var(--bu-warning);">
                            <span class="material-symbols-outlined">warning</span>
                        </div>
                        @if($summary['warnings'] > 0)
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium" style="background: rgba(245,158,11,0.2); color: #92400e;">
                                Requires Attention
                            </span>
                        @endif
                    </div>
                    <p class="text-on-surface-variant text-sm mb-1">Validation Issues</p>
                    <p class="text-2xl font-semibold" style="color: var(--bu-warning);">
                        {{ $summary['warnings'] }} {{ \Illuminate\Support\Str::plural('Warning', $summary['warnings']) }}
                    </p>
                </div>
            </div>

            <div class="border-b border-primary/10 overflow-x-auto">
                <nav class="flex space-x-8 min-w-max" aria-label="Preview tabs">
                    <button type="button" data-preview-tab="commitments"
                            class="preview-tab bu-tab-active whitespace-nowrap py-4 px-1 border-b-2 border-transparent text-sm transition-colors">
                        Commitments
                    </button>
                    <button type="button" data-preview-tab="deliverables"
                            class="preview-tab whitespace-nowrap py-4 px-1 border-b-2 border-transparent text-on-surface-variant hover:text-primary text-sm transition-colors">
                        Deliverables
                        <span class="ml-2 py-0.5 px-2 rounded-full text-xs" style="background: #dee4dd;">{{ $summary['deliverables'] }}</span>
                    </button>
                    <button type="button" data-preview-tab="kpis"
                            class="preview-tab whitespace-nowrap py-4 px-1 border-b-2 border-transparent text-on-surface-variant hover:text-primary text-sm transition-colors">
                        KPIs
                        <span class="ml-2 py-0.5 px-2 rounded-full text-xs" style="background: #dee4dd;">{{ $summary['kpis'] }}</span>
                    </button>
                </nav>
            </div>

            <div id="previewPanelCommitments" class="preview-panel bu-card border border-primary/10 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-primary/10 flex justify-between items-center" style="background: var(--bu-surface-bright);">
                    <h3 class="text-lg font-semibold text-on-background">Commitment Records</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                        <tr class="text-on-surface-variant border-b border-primary/10 text-xs font-semibold uppercase tracking-wider" style="background: var(--bu-surface-container-low);">
                            <th class="px-6 py-4 w-16">S/N</th>
                            <th class="px-6 py-4 min-w-[250px]">Commitment</th>
                            <th class="px-6 py-4 min-w-[150px]">Responsible Unit</th>
                            <th class="px-6 py-4">Target</th>
                            <th class="px-6 py-4 text-center">Q1-Q4 Actuals</th>
                            <th class="px-6 py-4 w-32">Status</th>
                        </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-primary/5">
                        @foreach($commitmentRows as $row)
                            <tr class="transition-colors {{ $row['status'] === 'at_risk' ? 'bg-warning/5 border-l-2 border-l-warning' : 'hover:bg-primary/5' }}">
                                <td class="px-6 py-4 text-on-surface-variant {{ $row['status'] === 'at_risk' ? 'pl-5' : '' }}">{{ $row['sn'] }}</td>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-on-background mb-1 flex items-center gap-2">
                                        {{ $row['deliverable'] }}
                                        @if($row['warning'])
                                            <span class="material-symbols-outlined text-[16px]" style="color: var(--bu-warning);" title="{{ $row['warning'] }}">error</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-on-surface-variant line-clamp-1">{{ $row['kpi'] }}</p>
                                    @if($row['warning'])
                                        <p class="text-xs line-clamp-1 mt-1" style="color: #92400e;">{{ $row['warning'] }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-on-surface-variant">{{ $row['responsible_unit'] }}</td>
                                <td class="px-6 py-4 text-on-background">{{ $row['target'] }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-1 h-6">
                                        @foreach($row['quarter_actuals'] as $percent)
                                            <div class="w-4 h-full rounded-sm relative" style="background: rgba(0,105,62,0.15);">
                                                <div class="absolute bottom-0 w-full rounded-sm" style="height: {{ $percent }}%; background: {{ $percent > 0 ? 'var(--bu-primary)' : 'rgba(110,122,112,0.2)' }};"></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($row['status'] === 'at_risk')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border" style="background: rgba(245,158,11,0.1); color: #92400e; border-color: rgba(245,158,11,0.2);">
                                            <span class="w-1.5 h-1.5 rounded-full mr-1.5" style="background: var(--bu-warning);"></span>
                                            At Risk
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border" style="background: rgba(16,185,129,0.1); color: var(--bu-success); border-color: rgba(16,185,129,0.2);">
                                            <span class="w-1.5 h-1.5 rounded-full mr-1.5" style="background: var(--bu-success);"></span>
                                            On Track
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="previewPanelDeliverables" class="preview-panel hidden bu-card border border-primary/10 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-primary/10" style="background: var(--bu-surface-bright);">
                    <h3 class="text-lg font-semibold text-on-background">Deliverable Records</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                        <tr class="text-on-surface-variant border-b border-primary/10 text-xs font-semibold uppercase tracking-wider" style="background: var(--bu-surface-container-low);">
                            <th class="px-6 py-4">S/N</th>
                            <th class="px-6 py-4">Deliverable</th>
                            <th class="px-6 py-4">KPI</th>
                            <th class="px-6 py-4">Target</th>
                            <th class="px-6 py-4">Q1 Actual</th>
                        </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-primary/5">
                        @foreach($preview['deliverables'] as $row)
                            <tr class="hover:bg-primary/5">
                                <td class="px-6 py-4 text-on-surface-variant">{{ $row['sn'] }}</td>
                                <td class="px-6 py-4 text-on-background">{{ $row['deliverable'] }}</td>
                                <td class="px-6 py-4 text-on-surface-variant">{{ $row['kpi'] }}</td>
                                <td class="px-6 py-4">{{ $row['target'] }}</td>
                                <td class="px-6 py-4">{{ $row['q1_actual'] !== '' ? $row['q1_actual'] : '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="previewPanelKpis" class="preview-panel hidden bu-card border border-primary/10 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-primary/10" style="background: var(--bu-surface-bright);">
                    <h3 class="text-lg font-semibold text-on-background">KPI Records</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                        <tr class="text-on-surface-variant border-b border-primary/10 text-xs font-semibold uppercase tracking-wider" style="background: var(--bu-surface-container-low);">
                            <th class="px-6 py-4">S/N</th>
                            <th class="px-6 py-4">KPI</th>
                            <th class="px-6 py-4">Deliverable</th>
                            <th class="px-6 py-4">Target</th>
                            <th class="px-6 py-4">Status</th>
                        </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-primary/5">
                        @foreach($preview['kpis'] as $row)
                            <tr class="hover:bg-primary/5">
                                <td class="px-6 py-4 text-on-surface-variant">{{ $row['sn'] }}</td>
                                <td class="px-6 py-4 text-on-background">{{ $row['kpi'] }}</td>
                                <td class="px-6 py-4 text-on-surface-variant">{{ $row['deliverable'] }}</td>
                                <td class="px-6 py-4">{{ $row['target'] }}</td>
                                <td class="px-6 py-4">{{ $row['status'] === 'at_risk' ? 'At Risk' : 'On Track' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($summary['warnings'] > 0)
                <div class="border-l-4 rounded-r-lg p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4" style="background: rgba(245,158,11,0.1); border-color: var(--bu-warning);">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined mt-0.5" style="color: var(--bu-warning);">warning</span>
                        <div>
                            <h4 class="font-semibold mb-1" style="color: #92400e;">Validation Alert</h4>
                            <p class="text-sm text-on-surface-variant">
                                {{ $summary['warnings'] }} {{ \Illuminate\Support\Str::plural('record', $summary['warnings']) }} require attention before final submission.
                                @if(($uploadMode ?? 'structure') === 'actuals')
                                    These include unmatched KPIs, missing milestones, or locked records that cannot be updated.
                                @else
                                    These include missing KPI descriptions and incomplete target or milestone values.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bu-card border border-primary/10 rounded-xl p-6 md:p-8 flex flex-col items-center text-center" style="background: #eaefe8;">
                <h3 class="text-xl font-semibold text-on-background mb-2">Ready to Submit?</h3>
                <p class="text-on-surface-variant max-w-2xl mb-6">
                    @if(($uploadMode ?? 'structure') === 'actuals')
                        By submitting, you confirm the quarterly actual values are accurate and ready for Sector Head approval.
                    @else
                        By submitting this data, you confirm that all commitments, deliverables, KPIs, annual targets, and quarterly milestones have been reviewed for this sector and fiscal year.
                    @endif
                </p>
                <form method="POST" action="{{ route('bulk-upload.submit') }}" class="w-full flex flex-col items-center">
                    @csrf
                    <label class="flex items-center gap-3 cursor-pointer mb-6 p-4 rounded-lg border border-transparent hover:border-primary/10 transition-colors">
                        <input type="checkbox" class="w-5 h-5 rounded border-2 border-primary/40 text-primary focus:ring-primary cursor-pointer"/>
                        <span class="text-sm font-medium text-on-background select-none">I confirm that the reviewed data is accurate and ready for final submission.</span>
                    </label>
                    <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                        <a href="{{ route('bulk-upload.index') }}"
                           class="bu-btn-outline px-6 py-3 rounded-lg font-medium text-center">
                            Back to Upload
                        </a>
                        <button type="submit"
                                class="bu-btn-primary px-6 py-3 rounded-lg font-medium shadow-sm flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">send</span>
                            Submit Performance Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        (function () {
            const tabs = document.querySelectorAll('.preview-tab');
            const panels = {
                commitments: document.getElementById('previewPanelCommitments'),
                deliverables: document.getElementById('previewPanelDeliverables'),
                kpis: document.getElementById('previewPanelKpis'),
            };

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    const target = tab.getAttribute('data-preview-tab');

                    tabs.forEach(function (item) {
                        item.classList.remove('bu-tab-active');
                        item.classList.add('text-on-surface-variant');
                    });
                    tab.classList.add('bu-tab-active');
                    tab.classList.remove('text-on-surface-variant');

                    Object.keys(panels).forEach(function (key) {
                        if (!panels[key]) return;
                        panels[key].classList.toggle('hidden', key !== target);
                    });
                });
            });
        })();
    </script>
@endsection
