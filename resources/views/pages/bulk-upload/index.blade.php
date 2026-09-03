@extends('layouts.app')

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
          rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#00693e",
                        "primary-container": "#008550",
                        "on-primary": "#ffffff",
                        "on-surface-variant": "#3e4a41",
                        "on-background": "#171d19",
                        "surface-bright": "#f5fbf3",
                        "surface-container-lowest": "#ffffff",
                        "background-light": "#f5f8f7",
                        "error": "#ef4444",
                        "success": "#10b981",
                    },
                    fontFamily: {
                        "display": ["Public Sans", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.125rem",
                        "lg": "0.25rem",
                        "xl": "0.5rem",
                    },
                },
            },
        }
    </script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
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

        /* Design tokens from design.html */
        .bulk-upload-page {
            --bu-background-light: #f5f8f7;
            --bu-surface-bright: #f5fbf3;
            --bu-surface-lowest: #ffffff;
            --bu-primary: #00693e;
            --bu-on-primary: #ffffff;
            --bu-on-surface-variant: #3e4a41;
            --bu-error: #ef4444;
            background-color: var(--bu-background-light);
        }

        .bulk-upload-card {
            background-color: var(--bu-surface-lowest);
        }

        .bulk-upload-card--bright {
            background-color: var(--bu-surface-bright);
        }

        .bulk-upload-field {
            background-color: var(--bu-background-light);
        }

        .bu-btn-outline {
            color: var(--bu-primary);
            border: 1px solid var(--bu-primary);
        }

        .bu-btn-outline:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .bu-btn-outline:hover:not(:disabled) {
            background-color: rgba(0, 105, 62, 0.05);
        }

        .bu-btn-outline .material-symbols-outlined {
            color: var(--bu-primary);
        }

        .bu-btn-primary {
            background-color: var(--bu-primary);
            color: var(--bu-on-primary);
        }

        .bu-btn-primary:hover {
            background-color: rgba(0, 105, 62, 0.9);
        }

        .bu-btn-primary .material-symbols-outlined {
            color: var(--bu-on-primary);
        }

        .bu-btn-cancel {
            color: var(--bu-on-surface-variant);
            border: 1px solid rgba(0, 105, 62, 0.2);
        }

        .bu-btn-cancel:hover {
            background-color: rgba(0, 105, 62, 0.05);
        }

        .bu-btn-danger {
            color: var(--bu-error);
        }

        .bu-btn-danger:hover {
            background-color: rgba(239, 68, 68, 0.1);
        }

        .bu-btn-danger .material-symbols-outlined {
            color: var(--bu-error);
        }
    </style>
@endsection

@section('content')
    <div class="bulk-upload-page p-4 md:p-8 font-display text-sm text-on-background min-h-full">
        <form id="bulkUploadForm" action="{{ route('bulk-upload.preview') }}" method="POST" enctype="multipart/form-data">
            @csrf
        <div class="max-w-6xl mx-auto space-y-8">
            @if(session('failure'))
                <div class="bulk-upload-card border border-error/20 rounded-xl p-4 text-sm" style="color: #ef4444; background: #ffdad6;">
                    {{ session('failure') }}
                </div>
            @endif
            @if($errors->any())
                <div class="bulk-upload-card border border-error/20 rounded-xl p-4 text-sm" style="color: #ef4444; background: #ffdad6;">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            {{-- Page Header & Breadcrumbs --}}
            <div>
                <nav class="text-sm text-on-surface-variant mb-2 flex items-center gap-2 flex-wrap">
                    @php
                        $user = auth()->user();
                        $dashboardRoute = $user && $user->isGovernor()
                            ? route('dashboard.statistics')
                            : route('dashboard');
                    @endphp
                    <a href="{{ $dashboardRoute }}" class="hover:text-primary transition-colors">Dashboard</a>
                    <span class="material-symbols-outlined text-xs">chevron_right</span>
                    <span class="hover:text-primary transition-colors">Performance Tracking</span>
                    <span class="material-symbols-outlined text-xs">chevron_right</span>
                    <span class="font-semibold text-primary">Bulk Upload</span>
                </nav>
                <h1 class="text-2xl md:text-[32px] font-bold tracking-tight text-on-background leading-tight">
                    @if(($uploadMode ?? 'structure') === 'actuals')
                        Bulk Actuals Upload
                    @else
                        Bulk Framework Structure Upload
                    @endif
                </h1>
                <p class="text-on-surface-variant mt-2 max-w-3xl">
                    @if(($uploadMode ?? 'structure') === 'actuals')
                        Download your sector template, enter quarterly actual values and remarks, then upload to submit performance data for Sector Head approval.
                    @elseif(!empty($supportsMultiSector))
                        Upload commitments, deliverables, KPIs, annual targets, and quarterly milestones for one sector or multiple sectors (one sheet per sector).
                        Quarterly actual values are entered later by Data Admins through the standard performance tracking workflow.
                    @else
                        Upload commitments, deliverables, KPIs, annual targets, and quarterly milestones for a sector in bulk.
                        Quarterly actual values are entered later by Data Admins through the standard performance tracking workflow.
                    @endif
                </p>
            </div>

            {{-- Main Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {{-- Left Column: Configuration --}}
                <div class="lg:col-span-1 space-y-6">
                    <div class="bulk-upload-card border border-primary/10 rounded-xl p-6 shadow-sm">
                        <h2 class="text-lg font-semibold mb-4 border-b border-primary/10 pb-2">Upload Configuration</h2>
                        <div class="space-y-5">
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-primary/80 mb-1"
                                       for="fiscalYear">
                                    Fiscal Year <span class="text-error">*</span>
                                </label>
                                <select id="fiscalYear" name="framework_id"
                                        class="bulk-upload-field w-full border border-primary/20 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                                        required>
                                    <option disabled value="">Select cycle</option>
                                    @foreach($frameworks as $framework)
                                        <option value="{{ $framework->id }}"
                                            {{ (string) old('framework_id', $defaultFrameworkId) === (string) $framework->id ? 'selected' : '' }}>
                                            {{ $framework->year }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if(!empty($supportsMultiSector))
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-primary/80 mb-2">
                                        Upload Scope <span class="text-error">*</span>
                                    </label>
                                    <div class="space-y-2">
                                        <label class="flex items-start gap-2 text-sm cursor-pointer">
                                            <input type="radio" name="sector_scope" value="single" id="sectorScopeSingle"
                                                   class="mt-1" {{ old('sector_scope', 'single') === 'single' ? 'checked' : '' }}>
                                            <span>
                                                <strong class="text-on-background">Single sector</strong>
                                                <span class="block text-on-surface-variant text-xs">One sheet template for one sector.</span>
                                            </span>
                                        </label>
                                        <label class="flex items-start gap-2 text-sm cursor-pointer">
                                            <input type="radio" name="sector_scope" value="multiple" id="sectorScopeMultiple"
                                                   class="mt-1" {{ old('sector_scope') === 'multiple' ? 'checked' : '' }}>
                                            <span>
                                                <strong class="text-on-background">Multiple sectors</strong>
                                                <span class="block text-on-surface-variant text-xs">One sheet per selected sector in the same workbook.</span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            @endif
                            <div id="singleSectorPicker">
                                <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-primary/80 mb-1"
                                       for="sector">
                                    Sector <span class="text-error">*</span>
                                </label>
                                <select id="sector" @if(!$sectorSelectionLocked) name="sector_id" @endif
                                        class="bulk-upload-field w-full border border-primary/20 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                                        required @if($sectorSelectionLocked) disabled @endif>
                                    <option disabled selected value="">Select sector</option>
                                </select>
                                @if($sectorSelectionLocked && $defaultSectorId)
                                    <input type="hidden" name="sector_id" value="{{ $defaultSectorId }}">
                                @endif
                            </div>
                            @if(!empty($supportsMultiSector))
                                <div id="multiSectorPicker" class="hidden">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-primary/80">
                                            Sectors <span class="text-error">*</span>
                                        </label>
                                        <button type="button" id="selectAllSectorsBtn"
                                                class="text-xs font-semibold text-primary hover:underline">
                                            Select all
                                        </button>
                                    </div>
                                    <div id="multiSectorList"
                                         class="bulk-upload-field border border-primary/20 rounded-lg max-h-48 overflow-y-auto p-2 space-y-1 text-sm">
                                    </div>
                                    <p id="multiSectorCount" class="text-xs text-on-surface-variant mt-2">0 sectors selected</p>
                                </div>
                            @endif
                            @if(($uploadMode ?? 'structure') === 'actuals')
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-primary/80 mb-1"
                                           for="reportingQuarter">
                                        Reporting Quarter <span class="text-error">*</span>
                                    </label>
                                    <select id="reportingQuarter" name="reporting_quarter" required
                                            class="bulk-upload-field w-full border border-primary/20 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                        @foreach([1,2,3,4] as $quarter)
                                            <option value="{{ $quarter }}" {{ (string) old('reporting_quarter', $defaultReportingQuarter ?? $entryQuarter) === (string) $quarter ? 'selected' : '' }}>
                                                Q{{ $quarter }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="pt-4 border-t border-primary/10">
                                <a href="{{ route('bulk-upload.template') }}" id="bulkUploadTemplateBtn"
                                   class="bu-btn-outline w-full py-2 px-4 rounded-lg font-semibold flex items-center justify-center gap-2 transition-all text-sm {{ !$uploadAllowed ? 'pointer-events-none opacity-50' : '' }}">
                                    <span class="material-symbols-outlined text-base">download</span>
                                    @if(($uploadMode ?? 'structure') === 'actuals')
                                        Download Sector Template
                                    @else
                                        Download Excel Template
                                    @endif
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Instruction Card --}}
                    <div class="bulk-upload-card--bright border border-primary/10 rounded-xl p-5 shadow-sm text-sm">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-primary mt-0.5">info</span>
                            <div>
                                <strong class="block text-on-background mb-1">Data Schema Rules</strong>
                                <ul class="list-disc pl-4 text-on-surface-variant space-y-1">
                                    <li>Do not modify template headers.</li>
                                    @if(($uploadMode ?? 'structure') === 'actuals')
                                        <li>Only edit quarterly Actual columns and Remarks.</li>
                                        <li>Targets and milestones are pre-filled by PDCU and must not be changed.</li>
                                        <li>Re-uploads update unlocked actuals; blank cells keep existing values; locked/approved rows are skipped.</li>
                                    @else
                                        <li>Ensure all mandatory target and milestone fields are complete.</li>
                                        <li>Re-uploads merge into existing data: unlocked fields are updated; blank cells are ignored; coordinator-confirmed milestones are skipped.</li>
                                        @if(!empty($supportsMultiSector))
                                            <li>For multi-sector uploads, keep one sector per sheet and do not rename sheet markers.</li>
                                        @endif
                                    @endif
                                    <li>Maximum file size is 50MB.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Upload Area --}}
                <div class="lg:col-span-2 flex flex-col gap-6">
                    <div id="bulkUploadClosedMessage"
                         class="bulk-upload-card--bright border border-primary/10 rounded-xl p-10 text-center shadow-sm min-h-[300px] flex flex-col items-center justify-center {{ $uploadAllowed ? 'hidden' : '' }}">
                        <div class="h-16 w-16 bg-primary/10 rounded-full flex items-center justify-center mb-4">
                            <span class="material-symbols-outlined text-3xl text-primary">lock</span>
                        </div>
                        <h3 class="text-lg font-semibold text-on-background mb-2">Upload Temporarily Unavailable</h3>
                        <p class="text-on-surface-variant max-w-lg mb-2">
                            @if(($uploadMode ?? 'structure') === 'actuals')
                                The data entry window for
                                <strong class="text-on-background" id="bulkUploadClosedQuarterLabel">Q{{ $defaultReportingQuarter ?? $entryQuarter }}</strong>
                            @else
                                The data entry window for Q{{ $entryQuarter }} {{ $entryYear }} is currently closed
                            @endif
                            @if($entryDeadline)
                                (deadline was {{ $entryDeadline->format('M d, Y') }})
                            @endif
                            .
                        </p>
                        <p class="text-on-surface-variant max-w-lg">
                            Please contact the <strong class="text-on-background">PDCU Coordinator</strong> to request an entry window extension before uploading performance data.
                        </p>
                    </div>

                    <div id="bulkUploadActiveArea" class="flex flex-col gap-6 {{ $uploadAllowed ? '' : 'hidden' }}">
                    <input type="file" id="bulkUploadFile" name="upload_file" class="hidden" accept=".xlsx,.csv"/>

                    {{-- Empty State Dropzone --}}
                    <div id="bulkUploadDropzone"
                         class="bulk-upload-card border-2 border-dashed border-primary/30 rounded-xl p-12 flex flex-col items-center justify-center text-center transition-all hover:border-primary hover:bg-primary/5 cursor-pointer min-h-[300px]">
                        <div class="h-16 w-16 bg-primary/10 rounded-full flex items-center justify-center mb-4">
                            <span class="material-symbols-outlined text-3xl text-primary">cloud_upload</span>
                        </div>
                        <h3 class="text-lg font-semibold text-on-background mb-2">Drag and drop your Excel file here</h3>
                        <p class="text-on-surface-variant mb-6 max-w-sm">
                            or click to browse from your computer. Only .xlsx and .csv files are supported.
                        </p>
                        <button type="button" id="bulkUploadBrowseBtn"
                                class="bu-btn-primary py-2.5 px-6 rounded-lg font-bold transition-colors shadow-sm">
                            Browse Files
                        </button>
                    </div>

                    {{-- File Selected State --}}
                    <div id="bulkUploadSelected" class="hidden bulk-upload-card border border-primary/10 rounded-xl p-6 shadow-sm">
                        <h3 class="text-base font-semibold mb-4 text-on-background">Selected File</h3>
                        <div class="flex items-center justify-between p-4 bulk-upload-field border border-primary/10 rounded-lg gap-4">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="h-10 w-10 bg-success/10 rounded flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-success">table</span>
                                </div>
                                <div class="min-w-0">
                                    <p id="bulkUploadFileName" class="font-semibold text-on-background truncate"></p>
                                    <p id="bulkUploadFileMeta" class="text-xs text-on-surface-variant"></p>
                                </div>
                            </div>
                            <button type="button" id="bulkUploadRemoveBtn" aria-label="Remove file"
                                    class="bu-btn-danger p-2 rounded-full transition-colors flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-sm">delete</span>
                            </button>
                        </div>
                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" id="bulkUploadCancelBtn"
                                    class="bu-btn-cancel py-2 px-4 rounded-lg font-semibold transition-all">
                                Cancel
                            </button>
                            <button type="submit" id="bulkUploadValidateBtn"
                                    class="bu-btn-primary py-2 px-6 rounded-lg font-bold transition-colors shadow-sm flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">check_circle</span>
                                Validate File
                            </button>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
        </form>
    </div>
@endsection

@section('js')
    <script>
        (function () {
            const sectorsByFramework = @json($sectorsByFramework);
            const fiscalYearSelect = document.getElementById('fiscalYear');
            const sectorSelect = document.getElementById('sector');
            const sectorSelectionLocked = @json($sectorSelectionLocked);
            const uploadMode = @json($uploadMode ?? 'structure');
            const supportsMultiSector = @json(!empty($supportsMultiSector));
            const templateBaseUrl = @json(route('bulk-upload.template'));
            const sectorEntryAccess = @json($sectorEntryAccess);
            const sectorQuarterEntryAccess = @json($sectorQuarterEntryAccess ?? []);
            const frameworkYears = @json($frameworkYears ?? []);
            const reportingQuarterSelect = document.getElementById('reportingQuarter');
            const closedQuarterLabel = document.getElementById('bulkUploadClosedQuarterLabel');
            const initialSectorId = @json(old('sector_id', $defaultSectorId));
            const initialSectorIds = @json(old('sector_ids', []));
            const initialSectorScope = @json(old('sector_scope', 'single'));
            const closedMessage = document.getElementById('bulkUploadClosedMessage');
            const activeArea = document.getElementById('bulkUploadActiveArea');
            const templateBtn = document.getElementById('bulkUploadTemplateBtn');
            const dropzone = document.getElementById('bulkUploadDropzone');
            const selectedPanel = document.getElementById('bulkUploadSelected');
            const fileInput = document.getElementById('bulkUploadFile');
            const browseBtn = document.getElementById('bulkUploadBrowseBtn');
            const removeBtn = document.getElementById('bulkUploadRemoveBtn');
            const cancelBtn = document.getElementById('bulkUploadCancelBtn');
            const uploadForm = document.getElementById('bulkUploadForm');
            const fileNameEl = document.getElementById('bulkUploadFileName');
            const fileMetaEl = document.getElementById('bulkUploadFileMeta');
            const singleSectorPicker = document.getElementById('singleSectorPicker');
            const multiSectorPicker = document.getElementById('multiSectorPicker');
            const multiSectorList = document.getElementById('multiSectorList');
            const multiSectorCount = document.getElementById('multiSectorCount');
            const selectAllSectorsBtn = document.getElementById('selectAllSectorsBtn');
            const sectorScopeSingle = document.getElementById('sectorScopeSingle');
            const sectorScopeMultiple = document.getElementById('sectorScopeMultiple');

            function getFrameworkYear() {
                const frameworkId = fiscalYearSelect.value;
                return frameworkYears[String(frameworkId)] || frameworkYears[frameworkId] || null;
            }

            function getReportingQuarter() {
                if (uploadMode !== 'actuals' || !reportingQuarterSelect) {
                    return null;
                }

                return parseInt(reportingQuarterSelect.value, 10) || null;
            }

            function getSectorScope() {
                if (!supportsMultiSector) {
                    return 'single';
                }

                const selected = document.querySelector('input[name="sector_scope"]:checked');
                return selected ? selected.value : 'single';
            }

            function getSelectedMultiSectorIds() {
                if (!multiSectorList) {
                    return [];
                }

                return Array.from(multiSectorList.querySelectorAll('input[type="checkbox"]:checked'))
                    .map(function (input) { return input.value; });
            }

            function isUploadAllowedForSector(sectorId) {
                if (!sectorId) {
                    return false;
                }

                if (uploadMode === 'actuals') {
                    const frameworkYear = getFrameworkYear();
                    const quarter = getReportingQuarter();

                    if (!frameworkYear || !quarter) {
                        return false;
                    }

                    const sectorAccess = sectorQuarterEntryAccess[String(sectorId)] || sectorQuarterEntryAccess[sectorId] || {};
                    const yearAccess = sectorAccess[String(frameworkYear)] || sectorAccess[frameworkYear] || {};

                    return yearAccess[String(quarter)] === true || yearAccess[quarter] === true;
                }

                return sectorEntryAccess[String(sectorId)] === true;
            }

            function updateClosedQuarterLabel() {
                if (!closedQuarterLabel) {
                    return;
                }

                const quarter = getReportingQuarter();
                const frameworkYear = getFrameworkYear();

                if (quarter && frameworkYear) {
                    closedQuarterLabel.textContent = 'Q' + quarter + ' ' + frameworkYear;
                }
            }

            function updateTemplateUrl() {
                if (!templateBtn) {
                    return;
                }

                const frameworkId = fiscalYearSelect.value;
                if (!frameworkId) {
                    templateBtn.href = templateBaseUrl;
                    return;
                }

                const params = new URLSearchParams({ framework_id: frameworkId });

                if (uploadMode === 'actuals') {
                    const sectorId = sectorSelect.value || initialSectorId;
                    if (!sectorId) {
                        templateBtn.href = templateBaseUrl;
                        return;
                    }
                    params.set('sector_id', sectorId);
                    templateBtn.href = templateBaseUrl + '?' + params.toString();
                    return;
                }

                const scope = getSectorScope();
                params.set('sector_scope', scope);

                if (scope === 'multiple') {
                    const sectorIds = getSelectedMultiSectorIds();
                    sectorIds.forEach(function (id) {
                        params.append('sector_ids[]', id);
                    });
                    templateBtn.href = sectorIds.length
                        ? templateBaseUrl + '?' + params.toString()
                        : templateBaseUrl;
                    return;
                }

                const sectorId = sectorSelect.value || initialSectorId;
                if (!sectorId) {
                    templateBtn.href = templateBaseUrl;
                    return;
                }
                params.set('sector_id', sectorId);
                templateBtn.href = templateBaseUrl + '?' + params.toString();
            }

            function updateUploadAvailability(sectorId) {
                let allowed = false;

                if (uploadMode === 'structure' && getSectorScope() === 'multiple') {
                    const selected = getSelectedMultiSectorIds();
                    allowed = selected.length > 0 && selected.every(function (id) {
                        return isUploadAllowedForSector(id);
                    });
                } else {
                    allowed = isUploadAllowedForSector(sectorId);
                }

                updateClosedQuarterLabel();

                if (closedMessage && activeArea) {
                    closedMessage.classList.toggle('hidden', allowed);
                    activeArea.classList.toggle('hidden', !allowed);
                }

                if (templateBtn) {
                    templateBtn.classList.toggle('pointer-events-none', !allowed);
                    templateBtn.classList.toggle('opacity-50', !allowed);
                }

                if (!allowed && typeof clearSelectedFile === 'function') {
                    clearSelectedFile();
                }
            }

            function updateMultiSectorCount() {
                if (!multiSectorCount) {
                    return;
                }

                const count = getSelectedMultiSectorIds().length;
                multiSectorCount.textContent = count + ' sector' + (count === 1 ? '' : 's') + ' selected';
            }

            function populateMultiSectors(frameworkId, selectedIds) {
                if (!multiSectorList) {
                    return;
                }

                const selected = (selectedIds || []).map(String);
                const sectors = sectorsByFramework[String(frameworkId)] || [];
                multiSectorList.innerHTML = '';

                sectors.forEach(function (sector) {
                    const label = document.createElement('label');
                    label.className = 'flex items-center gap-2 px-2 py-1.5 rounded hover:bg-primary/5 cursor-pointer';
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = 'sector_ids[]';
                    checkbox.value = String(sector.id);
                    checkbox.checked = selected.includes(String(sector.id));
                    checkbox.addEventListener('change', function () {
                        updateMultiSectorCount();
                        updateUploadAvailability(sectorSelect.value || initialSectorId);
                        updateTemplateUrl();
                    });
                    const text = document.createElement('span');
                    text.textContent = sector.name;
                    label.appendChild(checkbox);
                    label.appendChild(text);
                    multiSectorList.appendChild(label);
                });

                updateMultiSectorCount();
            }

            function updateSectorScopeUi() {
                const scope = getSectorScope();
                const isMultiple = scope === 'multiple';

                if (singleSectorPicker) {
                    singleSectorPicker.classList.toggle('hidden', isMultiple);
                }
                if (multiSectorPicker) {
                    multiSectorPicker.classList.toggle('hidden', !isMultiple);
                }

                if (sectorSelect) {
                    if (isMultiple) {
                        sectorSelect.removeAttribute('required');
                        sectorSelect.removeAttribute('name');
                    } else if (!sectorSelectionLocked) {
                        sectorSelect.setAttribute('required', 'required');
                        sectorSelect.setAttribute('name', 'sector_id');
                    }
                }

                updateUploadAvailability(sectorSelect.value || initialSectorId);
                updateTemplateUrl();
            }

            function populateSectors(frameworkId, selectedSectorId) {
                sectorSelect.innerHTML = '<option disabled value="">Select sector</option>';

                const sectors = sectorsByFramework[String(frameworkId)] || [];
                sectors.forEach(function (sector) {
                    const option = document.createElement('option');
                    option.value = sector.id;
                    option.textContent = sector.name;
                    if (selectedSectorId && String(selectedSectorId) === String(sector.id)) {
                        option.selected = true;
                    }
                    sectorSelect.appendChild(option);
                });

                if (!sectorSelectionLocked) {
                    sectorSelect.disabled = sectors.length === 0;
                } else {
                    sectorSelect.disabled = true;
                }

                if (!selectedSectorId && sectors.length === 1) {
                    sectorSelect.value = String(sectors[0].id);
                }

                populateMultiSectors(frameworkId, initialSectorIds.length ? initialSectorIds : []);
                updateSectorScopeUi();
            }

            fiscalYearSelect.addEventListener('change', function () {
                populateSectors(this.value);
            });

            if (fiscalYearSelect.value) {
                populateSectors(fiscalYearSelect.value, initialSectorId);
            } else {
                updateUploadAvailability(initialSectorId);
            }

            if (supportsMultiSector) {
                if (initialSectorScope === 'multiple' && sectorScopeMultiple) {
                    sectorScopeMultiple.checked = true;
                }
                [sectorScopeSingle, sectorScopeMultiple].forEach(function (radio) {
                    if (radio) {
                        radio.addEventListener('change', updateSectorScopeUi);
                    }
                });
                if (selectAllSectorsBtn) {
                    selectAllSectorsBtn.addEventListener('click', function () {
                        const checkboxes = multiSectorList
                            ? multiSectorList.querySelectorAll('input[type="checkbox"]')
                            : [];
                        const allChecked = Array.from(checkboxes).every(function (cb) { return cb.checked; });
                        checkboxes.forEach(function (cb) {
                            cb.checked = !allChecked;
                        });
                        selectAllSectorsBtn.textContent = allChecked ? 'Select all' : 'Clear all';
                        updateMultiSectorCount();
                        updateUploadAvailability(sectorSelect.value || initialSectorId);
                        updateTemplateUrl();
                    });
                }
                updateSectorScopeUi();
            }

            updateTemplateUrl();

            function formatFileSize(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
            }

            function showSelectedFile(file) {
                fileNameEl.textContent = file.name;
                fileMetaEl.textContent = formatFileSize(file.size) + ' • Selected just now';
                dropzone.classList.add('hidden');
                selectedPanel.classList.remove('hidden');
            }

            function clearSelectedFile() {
                fileInput.value = '';
                selectedPanel.classList.add('hidden');
                dropzone.classList.remove('hidden');
            }

            function handleFiles(files) {
                if (!files || !files.length) return;
                const file = files[0];
                const allowed = ['.xlsx', '.csv'];
                const ext = file.name.slice(file.name.lastIndexOf('.')).toLowerCase();
                if (!allowed.includes(ext)) {
                    alert('Only .xlsx and .csv files are supported.');
                    return;
                }
                if (getSectorScope() === 'multiple' && ext === '.csv') {
                    alert('Multi-sector upload requires an Excel workbook (.xlsx) with one sheet per sector.');
                    return;
                }
                showSelectedFile(file);
            }

            sectorSelect.addEventListener('change', function () {
                updateUploadAvailability(this.value);
                updateTemplateUrl();
            });

            if (reportingQuarterSelect) {
                reportingQuarterSelect.addEventListener('change', function () {
                    updateUploadAvailability(sectorSelect.value || initialSectorId);
                });
            }

            browseBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                fileInput.click();
            });

            dropzone.addEventListener('click', function () {
                fileInput.click();
            });

            dropzone.addEventListener('dragover', function (e) {
                e.preventDefault();
                dropzone.classList.add('border-primary', 'bg-primary/5');
            });

            dropzone.addEventListener('dragleave', function () {
                dropzone.classList.remove('border-primary', 'bg-primary/5');
            });

            dropzone.addEventListener('drop', function (e) {
                e.preventDefault();
                dropzone.classList.remove('border-primary', 'bg-primary/5');
                handleFiles(e.dataTransfer.files);
            });

            fileInput.addEventListener('change', function () {
                handleFiles(fileInput.files);
            });

            removeBtn.addEventListener('click', clearSelectedFile);
            cancelBtn.addEventListener('click', clearSelectedFile);

            if (uploadForm) {
                uploadForm.addEventListener('submit', function (event) {
                    if (!fiscalYearSelect.value) {
                        event.preventDefault();
                        alert('Please select a fiscal year.');
                        return;
                    }

                    const scope = getSectorScope();
                    if (scope === 'multiple') {
                        const sectorIds = getSelectedMultiSectorIds();
                        if (!sectorIds.length) {
                            event.preventDefault();
                            alert('Please select at least one sector.');
                            return;
                        }
                        if (!sectorIds.every(function (id) { return isUploadAllowedForSector(id); })) {
                            event.preventDefault();
                            alert('Upload is not available while the data entry window is closed for one or more selected sectors.');
                            return;
                        }
                    } else {
                        const sectorId = sectorSelect.value || initialSectorId;
                        if (!sectorId) {
                            event.preventDefault();
                            alert('Please select a sector.');
                            return;
                        }

                        if (!isUploadAllowedForSector(sectorId)) {
                            event.preventDefault();
                            if (uploadMode === 'actuals') {
                                alert('Upload is not available while the selected reporting quarter entry window is closed.');
                            } else {
                                alert('Upload is not available while the data entry window is closed.');
                            }
                            return;
                        }
                    }

                    if (uploadMode === 'actuals' && reportingQuarterSelect && !reportingQuarterSelect.value) {
                        event.preventDefault();
                        alert('Please select a reporting quarter.');
                        return;
                    }

                    if (!fileInput.files || !fileInput.files.length) {
                        event.preventDefault();
                        alert('Please select a file to validate.');
                    }
                });
            }
        })();
    </script>
@endsection
