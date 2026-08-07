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
        .material-symbols-outlined.fill {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .bulk-report-page {
            --bu-background-light: #f5f8f7;
            --bu-surface-bright: #f5fbf3;
            --bu-surface-lowest: #ffffff;
            --bu-primary: #00693e;
            --bu-on-primary: #ffffff;
            --bu-on-surface-variant: #3e4a41;
            --bu-on-background: #171d19;
            --bu-success: #10b981;
            --bu-warning: #f59e0b;
            background-color: var(--bu-background-light);
        }

        .bu-card { background-color: var(--bu-surface-lowest); }
        .bu-btn-outline {
            color: var(--bu-primary);
            border: 1px solid rgba(0, 105, 62, 0.2);
        }
        .bu-btn-outline:hover { background-color: rgba(0, 105, 62, 0.05); }
        .bu-btn-primary {
            background-color: var(--bu-primary);
            color: var(--bu-on-primary);
        }
        .bu-btn-primary:hover { background-color: #008550; }
        .bu-tab-active {
            border-bottom: 2px solid var(--bu-primary);
            color: var(--bu-primary);
            font-weight: 700;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
        .animate-fade-up {
            animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .animate-scale-in {
            animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        .delay-100 { animation-delay: 100ms; opacity: 0; }
        .delay-200 { animation-delay: 200ms; opacity: 0; }
        .delay-300 { animation-delay: 300ms; opacity: 0; }
    </style>
@endsection

@section('content')
    @php
        $meta = $report['meta'];
        $uploadMode = $report['upload_mode'] ?? 'structure';
        $isActualsUpload = $uploadMode === 'actuals';
        $submittedAt = $report['submitted_at'];
        $toneColors = [
            'primary' => ['text' => 'text-[#00693e]', 'bar' => 'bg-[#00693e]', 'track' => 'bg-[#00693e]/10', 'border' => 'hover:border-[#00693e]/20'],
            'success' => ['text' => 'text-[#10b981]', 'bar' => 'bg-[#10b981]', 'track' => 'bg-[#10b981]/10', 'border' => 'hover:border-[#10b981]/20'],
            'warning' => ['text' => 'text-[#f59e0b]', 'bar' => 'bg-[#f59e0b]', 'track' => 'bg-[#f59e0b]/10', 'border' => 'hover:border-[#f59e0b]/20'],
        ];

        $kpiRows = collect($report['kpis'] ?? []);

        $deliverableRows = collect($report['deliverables'] ?? []);

        $commitmentRows = collect($report['commitments'] ?? [])->map(function ($commitment) {
            return [
                'title' => $commitment['title'] ?? '—',
                'responsible_unit' => $commitment['responsible_unit'] ?? '—',
                'row_count' => count($commitment['rows'] ?? []),
            ];
        });
    @endphp

    <div class="bulk-report-page p-4 md:p-8 font-display text-sm text-on-background min-h-full">
        <main class="w-full max-w-5xl mx-auto flex flex-col gap-12 md:gap-16 py-4 md:py-8">
            <header class="flex flex-col items-center justify-center text-center gap-6 animate-fade-up">
                <div class="relative w-24 h-24 flex items-center justify-center rounded-full animate-scale-in" style="background: rgba(16,185,129,0.1);">
                    <span class="material-symbols-outlined fill text-[64px]" style="color: var(--bu-success);">check_circle</span>
                </div>
                <div class="space-y-2">
                    <h1 class="text-2xl md:text-[32px] font-bold tracking-tight" style="color: var(--bu-primary);">Submission Successful</h1>
                    <p class="text-base md:text-lg text-on-surface-variant flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-lg">receipt_long</span>
                        Ref: {{ $report['reference'] }}
                    </p>
                </div>
            </header>

            <section class="grid grid-cols-1 lg:grid-cols-12 gap-4 animate-fade-up delay-100">
                <div class="lg:col-span-5 bu-card border border-primary/10 rounded-xl p-6 md:p-8 shadow-sm flex flex-col justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-on-background mb-6 flex items-center gap-2">
                            <span class="material-symbols-outlined" style="color: var(--bu-primary);">info</span>
                            Metadata Record
                        </h2>
                        <div class="grid grid-cols-2 gap-y-6 gap-x-4">
                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Sector</span>
                                <span class="text-base font-medium text-on-background">{{ $meta['sector_name'] }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Reporting Year</span>
                                <span class="text-base font-medium text-on-background">FY {{ $meta['framework_year'] }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Submitted By</span>
                                <span class="text-base font-medium text-on-background">{{ $report['submitted_by'] }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Timestamp</span>
                                <span class="text-sm text-on-background">{{ $submittedAt->format('M j, Y - H:i') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 pt-4 border-t border-primary/10 flex items-center gap-2" style="color: var(--bu-success);">
                        <span class="material-symbols-outlined text-sm">verified_user</span>
                        <span class="text-sm font-medium">
                            @if($isActualsUpload)
                                Quarterly actuals saved and pending Sector Head approval
                            @else
                                Records saved to sector framework
                            @endif
                        </span>
                    </div>
                </div>

                <div class="lg:col-span-7 bu-card border border-primary/10 rounded-xl p-6 md:p-8 shadow-sm">
                    <h2 class="text-xl font-semibold text-on-background mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined" style="color: var(--bu-primary);">bar_chart</span>
                        @if($isActualsUpload)
                            Quarterly Performance
                        @else
                            Quarterly Target Distribution
                        @endif
                    </h2>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pb-4">
                        @foreach($report['quarterly_averages'] as $quarter)
                            @php $tone = $toneColors[$quarter['tone']] ?? $toneColors['primary']; @endphp
                            <div class="flex flex-col items-center justify-center p-4 rounded-lg border border-primary/5 relative overflow-hidden group transition-colors {{ $tone['border'] }}" style="background: var(--bu-background-light);">
                                <div class="absolute bottom-0 left-0 w-full h-1 {{ $tone['track'] }}">
                                    <div class="h-full {{ $tone['bar'] }}" style="width: {{ $quarter['percent'] }}%"></div>
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-2">{{ $quarter['label'] }}</span>
                                <span class="text-2xl md:text-[32px] font-bold {{ $tone['text'] }}">{{ $quarter['percent'] }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            @if(!empty($report['import_stats']))
                <section class="bu-card border border-primary/10 rounded-xl p-6 md:p-8 shadow-sm animate-fade-up delay-100">
                    <h2 class="text-xl font-semibold text-on-background mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined" style="color: var(--bu-primary);">inventory_2</span>
                        Import Summary
                    </h2>
                    @php $stats = $report['import_stats']; @endphp
                    @if($isActualsUpload)
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div class="p-4 rounded-lg border border-primary/10" style="background: var(--bu-background-light);">
                                <p class="text-on-surface-variant mb-1">Rows Processed</p>
                                <p class="font-semibold text-on-background">{{ $stats['rows_processed'] ?? 0 }}</p>
                            </div>
                            <div class="p-4 rounded-lg border border-primary/10" style="background: var(--bu-background-light);">
                                <p class="text-on-surface-variant mb-1">Actuals Updated</p>
                                <p class="font-semibold text-on-background">{{ $stats['actuals_updated'] ?? 0 }}</p>
                            </div>
                            <div class="p-4 rounded-lg border border-primary/10" style="background: var(--bu-background-light);">
                                <p class="text-on-surface-variant mb-1">New Submissions</p>
                                <p class="font-semibold text-on-background">{{ $stats['actuals_submitted'] ?? 0 }}</p>
                            </div>
                            <div class="p-4 rounded-lg border border-primary/10" style="background: var(--bu-background-light);">
                                <p class="text-on-surface-variant mb-1">Skipped (Locked)</p>
                                <p class="font-semibold text-on-background">{{ $stats['skipped_locked'] ?? 0 }}</p>
                            </div>
                        </div>
                        @if(($stats['skipped_no_kpi'] ?? 0) > 0 || ($stats['skipped_no_milestone'] ?? 0) > 0)
                            <p class="text-sm text-on-surface-variant mt-4">
                                {{ ($stats['skipped_no_kpi'] ?? 0) + ($stats['skipped_no_milestone'] ?? 0) }}
                                record(s) could not be matched to an existing KPI or quarterly milestone.
                            </p>
                        @endif
                    @else
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div class="p-4 rounded-lg border border-primary/10" style="background: var(--bu-background-light);">
                                <p class="text-on-surface-variant mb-1">Commitments</p>
                                <p class="font-semibold text-on-background">{{ $stats['commitments_created'] }} new / {{ $stats['commitments_matched'] }} matched</p>
                            </div>
                            <div class="p-4 rounded-lg border border-primary/10" style="background: var(--bu-background-light);">
                                <p class="text-on-surface-variant mb-1">Deliverables</p>
                                <p class="font-semibold text-on-background">{{ $stats['deliverables_created'] }} new / {{ $stats['deliverables_matched'] }} matched</p>
                            </div>
                            <div class="p-4 rounded-lg border border-primary/10" style="background: var(--bu-background-light);">
                                <p class="text-on-surface-variant mb-1">KPIs</p>
                                <p class="font-semibold text-on-background">{{ $stats['kpis_created'] }} new / {{ $stats['kpis_matched'] }} matched</p>
                            </div>
                            <div class="p-4 rounded-lg border border-primary/10" style="background: var(--bu-background-light);">
                                <p class="text-on-surface-variant mb-1">Milestones</p>
                                <p class="font-semibold text-on-background">{{ $stats['milestones_created'] }} new / {{ $stats['milestones_updated'] }} updated</p>
                            </div>
                        </div>
                        @if(($stats['milestones_skipped'] ?? 0) > 0)
                            <p class="text-sm text-on-surface-variant mt-4">
                                {{ $stats['milestones_skipped'] }} quarterly milestone(s) were skipped because Data Admins have already entered actual values.
                            </p>
                        @endif
                    @endif
                </section>
            @endif

            <section class="bu-card border border-primary/10 rounded-xl shadow-sm overflow-hidden animate-fade-up delay-200">
                <div class="flex border-b border-primary/10 px-4" style="background: var(--bu-background-light);">
                    <button type="button" class="report-tab bu-tab-active px-6 py-4 text-sm" data-report-tab="kpis">KPIs</button>
                    <button type="button" class="report-tab px-6 py-4 text-sm text-on-surface-variant hover:text-primary hover:bg-primary/5 transition-colors" data-report-tab="deliverables">Deliverables</button>
                    <button type="button" class="report-tab px-6 py-4 text-sm text-on-surface-variant hover:text-primary hover:bg-primary/5 transition-colors" data-report-tab="commitments">Commitments</button>
                </div>

                <div id="reportPanelKpis" class="overflow-x-auto p-4">
                    <table class="w-full text-left border-collapse">
                        <thead>
                        <tr class="bg-surface-variant/30">
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">KPI</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Deliverable</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Full Year Target</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant text-right">Full Year Actual</th>
                        </tr>
                        </thead>
                        <tbody class="text-sm text-on-background">
                        @forelse($kpiRows as $row)
                            <tr class="border-b border-primary/5 hover:bg-primary/5 transition-colors">
                                <td class="p-4 font-medium">{{ $row['kpi'] ?: '—' }}</td>
                                <td class="p-4">{{ $row['deliverable'] ?? '—' }}</td>
                                <td class="p-4">{{ $row['full_year_target'] !== '' ? $row['full_year_target'] : '—' }}</td>
                                <td class="p-4 text-right font-medium" style="color: var(--bu-primary);">{{ $row['full_year_actual'] !== '' ? $row['full_year_actual'] : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-4 text-center text-on-surface-variant">No KPI records in this submission.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="reportPanelDeliverables" class="overflow-x-auto p-4 hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                        <tr class="bg-surface-variant/30">
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Deliverable</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">KPI</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Full Year Target</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant text-right">Full Year Actual</th>
                        </tr>
                        </thead>
                        <tbody class="text-sm text-on-background">
                        @forelse($deliverableRows as $row)
                            <tr class="border-b border-primary/5 hover:bg-primary/5 transition-colors">
                                <td class="p-4 font-medium">{{ $row['deliverable'] ?: '—' }}</td>
                                <td class="p-4">{{ $row['kpi'] ?: '—' }}</td>
                                <td class="p-4">{{ $row['full_year_target'] !== '' ? $row['full_year_target'] : '—' }}</td>
                                <td class="p-4 text-right font-medium" style="color: var(--bu-primary);">{{ $row['full_year_actual'] !== '' ? $row['full_year_actual'] : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-4 text-center text-on-surface-variant">No deliverable records in this submission.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="reportPanelCommitments" class="overflow-x-auto p-4 hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                        <tr class="bg-surface-variant/30">
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Commitment</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Responsible Unit</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant text-right">Records</th>
                        </tr>
                        </thead>
                        <tbody class="text-sm text-on-background">
                        @forelse($commitmentRows as $row)
                            <tr class="border-b border-primary/5 hover:bg-primary/5 transition-colors">
                                <td class="p-4 font-medium">{{ $row['title'] }}</td>
                                <td class="p-4">{{ $row['responsible_unit'] }}</td>
                                <td class="p-4 text-right font-medium" style="color: var(--bu-primary);">{{ $row['row_count'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-4 text-center text-on-surface-variant">No commitment records in this submission.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bu-card border border-primary/10 rounded-xl p-6 md:p-8 shadow-sm animate-fade-up delay-300">
                <h2 class="text-xl font-semibold text-on-background mb-8 flex items-center gap-2">
                    <span class="material-symbols-outlined" style="color: var(--bu-primary);">history</span>
                    Audit Trail
                </h2>
                <div class="relative border-l-2 border-primary/10 ml-3 pl-8 py-2 space-y-8">
                    @foreach($report['audit_trail'] as $event)
                        <div class="relative">
                            <div class="absolute -left-[41px] top-1 h-4 w-4 rounded-full border-2 border-white flex items-center justify-center {{ $event['active'] ? 'bg-primary/30' : 'bg-primary/10' }}">
                                <div class="rounded-full bg-primary {{ $event['active'] ? 'h-2 w-2' : 'h-1.5 w-1.5 opacity-60' }}"></div>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-2">
                                <h3 class="text-base font-medium text-on-background">{{ $event['title'] }}</h3>
                                <span class="text-sm text-on-surface-variant">{{ $event['timestamp']->format('M j, Y - H:i:s') }} UTC</span>
                            </div>
                            <p class="text-sm text-on-surface-variant mt-1">{{ $event['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <footer class="flex flex-col-reverse sm:flex-row items-center justify-between gap-4 pt-4 border-t border-primary/10 animate-fade-up delay-300">
                <a href="{{ $dashboardRoute }}"
                   class="w-full sm:w-auto px-6 py-3 text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2 hover:bg-primary/5"
                   style="color: var(--bu-primary);">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                    Back to Dashboard
                </a>
                <div class="flex flex-col sm:flex-row w-full sm:w-auto gap-3">
                    <a href="{{ route('bulk-upload.index') }}"
                       class="bu-btn-outline w-full sm:w-auto px-6 py-3 text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2 shadow-sm">
                        <span class="material-symbols-outlined text-sm">upload_file</span>
                        New Upload
                    </a>
                    <a href="{{ route('bulk-upload.report.data') }}"
                       class="bu-btn-outline w-full sm:w-auto px-6 py-3 text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2 shadow-sm">
                        <span class="material-symbols-outlined text-sm">table</span>
                        Download Data
                    </a>
                    <a href="{{ route('bulk-upload.report.print', ['print' => 1]) }}"
                       target="_blank" rel="noopener"
                       class="bu-btn-primary w-full sm:w-auto px-6 py-3 text-sm font-medium rounded-lg transition-all flex items-center justify-center gap-2 shadow-sm">
                        <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                        Download Report
                    </a>
                </div>
            </footer>
        </main>
    </div>
@endsection

@section('js')
    <script>
        (function () {
            const tabs = document.querySelectorAll('.report-tab');
            const panels = {
                kpis: document.getElementById('reportPanelKpis'),
                deliverables: document.getElementById('reportPanelDeliverables'),
                commitments: document.getElementById('reportPanelCommitments'),
            };

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    const target = tab.getAttribute('data-report-tab');

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
