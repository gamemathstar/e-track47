@php use Carbon\Carbon; @endphp
@extends('layouts.app')

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
          rel="stylesheet"/>
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
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            -webkit-font-feature-settings: 'liga';
            -webkit-font-smoothing: antialiased;
        }

        .confirmation-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid #e2e8f0;
            max-width: 900px;
            margin: 0 auto;
        }

        .header-banner {
            height: 12rem;
            width: 100%;
            position: relative;
            overflow: hidden;
            background-color: rgba(0, 133, 80, 0.1);
        }

        .header-gradient {
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, rgba(0, 133, 80, 0.4), transparent);
            display: flex;
            align-items: center;
            padding: 0 3rem;
        }

        .header-icon-bg {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            width: 33.333%;
            opacity: 0.1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-box {
            background-color: #f6f8f7;
            padding: 1.5rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(0, 133, 80, 0.2);
            text-align: center;
        }

        .warning-box {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            margin-bottom: 2.5rem;
            display: flex;
            gap: 1rem;
        }

        .btn-back {
            color: #475569;
            font-weight: 600;
            padding: 0.75rem 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: color 0.15s;
            text-decoration: none;
            border: none;
            background: none;
            cursor: pointer;
        }

        .btn-back:hover {
            color: #0f172a;
        }

        .btn-confirm {
            background-color: #008550;
            color: white;
            font-weight: bold;
            padding: 0.75rem 2.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 133, 80, 0.2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.15s;
            border: none;
            cursor: pointer;
        }

        .btn-confirm:hover {
            background-color: rgba(0, 133, 80, 0.9);
            transform: scale(1.02);
        }

        .btn-confirm:active {
            transform: scale(0.98);
        }

        .footer-details {
            padding: 1rem 2.5rem;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #64748b;
        }

        .structure-preview {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .structure-item {
            border-bottom: 1px solid #e2e8f0;
        }

        .structure-item:last-child {
            border-bottom: none;
        }

        .structure-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            cursor: pointer;
            list-style: none;
            transition: background-color 0.15s;
        }

        .structure-summary:hover {
            background-color: #f8fafc;
        }

        .structure-summary-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .structure-chevron {
            color: #94a3b8;
            transition: transform 0.15s;
        }

        .structure-item[open] .structure-chevron {
            transform: rotate(90deg);
        }

        .structure-badge {
            padding: 0.125rem 0.625rem;
            border-radius: 9999px;
            background-color: #f1f5f9;
            font-size: 0.75rem;
            font-weight: 500;
            color: #475569;
        }

        .structure-details {
            padding: 1rem 1rem 1rem 0;
            background-color: rgba(248, 250, 252, 0.5);
        }

        .structure-commitments {
            margin-left: 2.25rem;
            border-left: 2px solid rgba(0, 133, 80, 0.2);
            padding: 0.5rem 0;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .structure-commitment-item {
            padding-left: 1rem;
            position: relative;
        }

        .structure-commitment-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 0.5rem;
            height: 2px;
            background-color: rgba(0, 133, 80, 0.2);
        }

        details > summary::-webkit-details-marker {
            display: none;
        }

        .sector-checkbox {
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid #cbd5e1;
            border-radius: 0.25rem;
            cursor: pointer;
            margin-right: 0.75rem;
            flex-shrink: 0;
            accent-color: #008550;
        }

        .sector-checkbox:checked {
            background-color: #008550;
            border-color: #008550;
        }

        .sector-checkbox:indeterminate {
            background-color: #008550;
            border-color: #008550;
        }

        .check-all-container {
            padding: 0.75rem 1rem;
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            color: #0f172a;
        }

        .structure-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            cursor: pointer;
            list-style: none;
            transition: background-color 0.15s;
        }

        .structure-summary:hover {
            background-color: #f8fafc;
        }

        .structure-summary-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }
    </style>
@endsection

@section('content')
    <div class="content">
        <div
            style="padding: 3rem 1rem; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh;">
            <!-- Breadcrumbs -->
            <div
                style="width: 100%; max-width: 900px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem;">
                <a href="{{ route('frameworks.index') }}"
                   style="color: #008550; text-decoration: underline; display: flex; align-items: center; gap: 0.25rem;">
                    <span class="material-symbols-outlined" style="font-size: 0.875rem;">home</span>
                    Frameworks
                </a>
                <span style="color: #94a3b8;">/</span>
                <span style="color: #0f172a; font-weight: 500;">Inherit Structure</span>
            </div>

            <!-- Confirmation Card -->
            <div class="confirmation-card">
                <!-- Header Banner -->
                <div class="header-banner">
                    <div class="header-gradient">
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <span class="material-symbols-outlined" style="font-size: 3.75rem; color: #008550;">content_copy</span>
                            <h1 style="font-size: 1.5rem; font-weight: bold; color: #0f172a; margin: 0;">Confirm
                                Framework Inheritance</h1>
                        </div>
                    </div>
                    <div class="header-icon-bg">
                        <span class="material-symbols-outlined"
                              style="font-size: 11.25rem; transform: rotate(12deg); color: #008550;">history_edu</span>
                    </div>
                </div>

                <div style="padding: 2.5rem;">
                    <!-- Success/Error Messages -->
                    @if(session('success'))
                        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg flex items-center justify-between mb-4">
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
                        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined">error</span>
                                <span>{{ session('failure') }}</span>
                            </div>
                            <button type="button" class="text-red-600 hover:text-red-800" onclick="this.parentElement.remove()">
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="material-symbols-outlined">error</span>
                                <span class="font-bold">Please fix the following errors:</span>
                            </div>
                            <ul class="list-disc list-inside text-sm space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <!-- Description -->
                    <div style="margin-bottom: 2rem;">
                        <p style="font-size: 1.125rem; color: #334155; line-height: 1.625;">
                            You are about to copy the <span
                                style="font-weight: bold; color: #0f172a;">{{ $sourceFramework->title }}</span>
                            into <span style="font-weight: bold; color: #0f172a;">{{ $validated['year'] }}</span>.
                            This action will initialize the new reporting cycle based on the existing hierarchy.
                        </p>
                    </div>

                    <!-- Summary Grid -->
                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                        <div class="stat-box">
                            <p style="color: #008550; font-size: 1.875rem; font-weight: bold; margin: 0 0 0.25rem 0;">{{ $sectorsCount }}</p>
                            <p style="color: #64748b; font-size: 0.875rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">
                                Sectors</p>
                        </div>
                        <div class="stat-box">
                            <p style="color: #008550; font-size: 1.875rem; font-weight: bold; margin: 0 0 0.25rem 0;">{{ $commitmentsCount }}</p>
                            <p style="color: #64748b; font-size: 0.875rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">
                                Commitments</p>
                        </div>
                        <div class="stat-box">
                            <p style="color: #008550; font-size: 1.875rem; font-weight: bold; margin: 0 0 0.25rem 0;">{{ $deliverablesCount }}</p>
                            <p style="color: #64748b; font-size: 0.875rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">
                                Deliverables</p>
                        </div>
                        <div class="stat-box">
                            <p style="color: #008550; font-size: 1.875rem; font-weight: bold; margin: 0 0 0.25rem 0;">{{ $kpisCount }}</p>
                            <p style="color: #64748b; font-size: 0.875rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">
                                KPIs</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <form action="{{ route('frameworks.store') }}" method="POST" id="inherit-form">
                        @csrf
                        <input type="hidden" name="creation_method" value="inherit">
                        <input type="hidden" name="year" value="{{ $validated['year'] }}">
                        <input type="hidden" name="title" value="{{ $validated['title'] }}">
                        <input type="hidden" name="description" value="{{ $validated['description'] ?? '' }}">
                        <input type="hidden" name="source_framework_id" value="{{ $sourceFramework->id }}">
                        <input type="hidden" name="status" value="Draft">

                        <!-- Structure Preview -->
                        @if(isset($sectors) && $sectors->count() > 0)
                            <div style="margin-bottom: 2.5rem;">
                                <h3 style="font-size: 0.875rem; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <span class="material-symbols-outlined"
                                          style="font-size: 1.125rem; color: #008550;">account_tree</span>
                                    Select Sectors to Inherit
                                </h3>
                                <div class="structure-preview">
                                    <!-- Check All -->
                                    <div class="check-all-container">
                                        <input type="checkbox" id="check-all-sectors" class="sector-checkbox" checked>
                                        <label for="check-all-sectors" style="cursor: pointer; flex: 1;">
                                            <strong>Select All Sectors ({{ $sectors->count() }})</strong>
                                        </label>
                                    </div>
                                    @foreach($sectors as $sector)
                                        <details class="structure-item">
                                            <summary class="structure-summary">
                                                <div class="structure-summary-content">
                                                    <input type="checkbox"
                                                           name="selected_sector_ids[]"
                                                           value="{{ $sector->id }}"
                                                           class="sector-checkbox sector-checkbox-item"
                                                           checked
                                                           onclick="event.stopPropagation();">
                                                    <span class="material-symbols-outlined structure-chevron">chevron_right</span>
                                                    <span
                                                        style="font-weight: 600; color: #1e293b;">{{ $sector->sector_name }}</span>
                                                </div>
                                                <span class="structure-badge">
                                            {{ $sector->commitments->count() }} {{ Str::plural('Commitment', $sector->commitments->count()) }}
                                        </span>
                                            </summary>
                                            <div class="structure-details">
                                                @if($sector->commitments->count() > 0)
                                                    <ul class="structure-commitments">
                                                        @foreach($sector->commitments as $commitment)
                                                            <li class="structure-commitment-item">
                                                                <p style="font-size: 0.875rem; font-weight: 500; color: #334155; margin: 0;">{{ $commitment->name }}</p>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <div
                                                        style="padding: 1rem 0 1rem 3rem; color: #64748b; font-size: 0.875rem; font-style: italic;">
                                                        No commitments for this sector
                                                    </div>
                                                @endif
                                            </div>
                                        </details>
                                    @endforeach
                                </div>
                                <p style="margin-top: 0.75rem; font-size: 0.75rem; color: #94a3b8; text-align: center;">
                                    Select which sectors and their associated commitments, deliverables, and KPIs to
                                    inherit.
                                </p>
                            </div>
                        @endif

                        <!-- Warning Alert -->
                        <div class="warning-box">
                            <span class="material-symbols-outlined"
                                  style="color: #f59e0b; flex-shrink: 0;">warning</span>
                            <div>
                                <h4 style="color: #92400e; font-weight: bold; font-size: 0.875rem; margin: 0 0 0.25rem 0;">
                                    Action Warning</h4>
                                <p style="color: #b45309; font-size: 0.875rem; margin: 0.25rem 0 0 0; line-height: 1.5;">
                                    KPI targets and performance data will <span
                                        style="font-weight: bold; text-decoration: underline;">not</span> be
                                    copied. Only the structural hierarchy, definitions, and associations will be
                                    inherited.
                                </p>
                            </div>
                        </div>

                        <div
                            style="display: flex; flex-direction: row; gap: 1rem; width: 100%; justify-content: flex-end; align-items: center; flex-wrap: wrap; margin-top: 1rem;">
                            <a href="{{ route('frameworks.create') }}" class="btn-back"
                               style="width: 100%; max-width: auto;">
                                <span class="material-symbols-outlined" style="font-size: 1.125rem;">arrow_back</span>
                                Go Back
                            </a>
                            <button type="submit" class="btn-confirm" style="width: 100%; max-width: auto;">
                                <span class="material-symbols-outlined">rocket_launch</span>
                                Confirm & Initialize
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Footer Details -->
                <div class="footer-details">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                    <span style="display: flex; align-items: center; gap: 0.25rem;">
                        <span class="material-symbols-outlined" style="font-size: 0.75rem;">history</span>
                        Last updated: {{ $sourceFramework->updated_at ? Carbon::parse($sourceFramework->updated_at)->format('M d, Y') : 'N/A' }}
                    </span>
                        @if($sourceFramework->creator)
                            <span style="display: flex; align-items: center; gap: 0.25rem;">
                            <span class="material-symbols-outlined" style="font-size: 0.75rem;">person</span>
                            Admin: {{ $sourceFramework->creator->name ?? 'N/A' }}
                        </span>
                        @endif
                    </div>
                    <p style="margin: 0;">Ref ID: FW-{{ $validated['year'] }}
                        -INIT-{{ str_pad($sourceFramework->id, 3, '0', STR_PAD_LEFT) }}</p>
                </div>
            </div>

            <!-- Help Link -->
            <p style="margin-top: 2rem; color: #64748b; font-size: 0.875rem;">
                Need help with the inheritance process?
                <a href="#" style="color: #008550; font-weight: 500; text-decoration: underline;">View the
                    documentation</a>.
            </p>
        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkAllCheckbox = document.getElementById('check-all-sectors');
            const sectorCheckboxes = document.querySelectorAll('.sector-checkbox-item');

            // Check all functionality
            if (checkAllCheckbox) {
                checkAllCheckbox.addEventListener('change', function () {
                    sectorCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }

            // Update check all when individual checkboxes change
            sectorCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    const allChecked = Array.from(sectorCheckboxes).every(cb => cb.checked);
                    const noneChecked = Array.from(sectorCheckboxes).every(cb => !cb.checked);

                    if (checkAllCheckbox) {
                        if (allChecked) {
                            checkAllCheckbox.checked = true;
                            checkAllCheckbox.indeterminate = false;
                        } else if (noneChecked) {
                            checkAllCheckbox.checked = false;
                            checkAllCheckbox.indeterminate = false;
                        } else {
                            checkAllCheckbox.checked = false;
                            checkAllCheckbox.indeterminate = true;
                        }
                    }
                });
            });

            // Form validation - ensure at least one sector is selected
            const form = document.getElementById('inherit-form');
            if (form) {
                form.addEventListener('submit', function (e) {
                    const checkedSectors = Array.from(sectorCheckboxes).filter(cb => cb.checked);
                    if (checkedSectors.length === 0) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'No Sectors Selected',
                            text: 'Please select at least one sector to inherit.',
                            confirmButtonColor: '#008550'
                        });
                        return false;
                    }
                });
            }
        });
    </script>
@endsection
