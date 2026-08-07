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
                            <div>
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
                            @if(($uploadMode ?? 'structure') === 'actuals')
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-primary/80 mb-1"
                                           for="reportingQuarter">
                                        Reporting Quarter
                                    </label>
                                    <select id="reportingQuarter" name="reporting_quarter"
                                            class="bulk-upload-field w-full border border-primary/20 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                        <option value="">All quarters with values</option>
                                        @foreach([1,2,3,4] as $quarter)
                                            <option value="{{ $quarter }}" {{ (string) old('reporting_quarter', $entryQuarter) === (string) $quarter ? 'selected' : '' }}>
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
                                    @else
                                        <li>Ensure all mandatory target and milestone fields are complete.</li>
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
                            The data entry window for Q{{ $entryQuarter }} {{ $entryYear }} is currently closed
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
            const templateBaseUrl = @json(route('bulk-upload.template'));
            const sectorEntryAccess = @json($sectorEntryAccess);
            const initialSectorId = @json(old('sector_id', $defaultSectorId));
            const closedMessage = document.getElementById('bulkUploadClosedMessage');
            const activeArea = document.getElementById('bulkUploadActiveArea');
            const templateBtn = document.getElementById('bulkUploadTemplateBtn');
            const dropzone = document.getElementById('bulkUploadDropzone');
            const selectedPanel = document.getElementById('bulkUploadSelected');
            const fileInput = document.getElementById('bulkUploadFile');
            const browseBtn = document.getElementById('bulkUploadBrowseBtn');
            const removeBtn = document.getElementById('bulkUploadRemoveBtn');
            const cancelBtn = document.getElementById('bulkUploadCancelBtn');
            const validateBtn = document.getElementById('bulkUploadValidateBtn');
            const uploadForm = document.getElementById('bulkUploadForm');
            const fileNameEl = document.getElementById('bulkUploadFileName');
            const fileMetaEl = document.getElementById('bulkUploadFileMeta');

            function isUploadAllowedForSector(sectorId) {
                if (!sectorId) {
                    return false;
                }

                return sectorEntryAccess[String(sectorId)] === true;
            }

            function updateTemplateUrl() {
                if (!templateBtn || uploadMode !== 'actuals') {
                    return;
                }

                const frameworkId = fiscalYearSelect.value;
                const sectorId = sectorSelect.value || initialSectorId;
                if (!frameworkId || !sectorId) {
                    templateBtn.href = templateBaseUrl;
                    return;
                }

                const params = new URLSearchParams({
                    framework_id: frameworkId,
                    sector_id: sectorId,
                });
                templateBtn.href = templateBaseUrl + '?' + params.toString();
            }

            function updateUploadAvailability(sectorId) {
                const allowed = isUploadAllowedForSector(sectorId);

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

                updateUploadAvailability(sectorSelect.value || selectedSectorId);
                updateTemplateUrl();
            }

            fiscalYearSelect.addEventListener('change', function () {
                populateSectors(this.value);
                updateTemplateUrl();
            });

            if (fiscalYearSelect.value) {
                populateSectors(fiscalYearSelect.value, initialSectorId);
            } else {
                updateUploadAvailability(initialSectorId);
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
                showSelectedFile(file);
            }

            sectorSelect.addEventListener('change', function () {
                updateUploadAvailability(this.value);
                updateTemplateUrl();
            });

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

                    const sectorId = sectorSelect.value || initialSectorId;
                    if (!sectorId) {
                        event.preventDefault();
                        alert('Please select a sector.');
                        return;
                    }

                    if (!isUploadAllowedForSector(sectorId)) {
                        event.preventDefault();
                        alert('Upload is not available while the data entry window is closed.');
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
