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

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(0, 133, 80, 0.1);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .status-badge-active {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: bold;
            background-color: rgba(0, 133, 80, 0.1);
            color: #008550;
            border: 1px solid rgba(0, 133, 80, 0.2);
        }

        .status-badge-archived {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .pulse-dot {
            width: 6px;
            height: 6px;
            background-color: #008550;
            border-radius: 50%;
            margin-right: 6px;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .5;
            }
        }

        .framework-table-container {
            background: white;
            border-radius: 0.75rem;
            border: 1px solid rgba(0, 133, 80, 0.1);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .framework-table thead tr {
            background-color: rgba(0, 133, 80, 0.05);
        }

        .framework-table thead th {
            padding: 1rem 1.5rem;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
        }

        .framework-table tbody tr {
            transition: background-color 0.15s;
        }

        .framework-table tbody tr:hover {
            background-color: rgba(0, 133, 80, 0.02);
        }

        .framework-table tbody tr.active-row {
            background-color: rgba(0, 133, 80, 0.05);
        }

        .framework-table tbody td {
            padding: 1rem 1.5rem;
        }

        .pagination-container {
            padding: 1rem 1.5rem;
            background-color: #f8fafc;
            border-top: 1px solid rgba(0, 133, 80, 0.1);
        }

        .pagination-btn {
            padding: 0.375rem;
            border-radius: 0.25rem;
            background: white;
            border: 1px solid rgba(0, 133, 80, 0.1);
            color: #64748b;
            font-size: 0.75rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.15s;
        }

        .pagination-btn:hover:not(.disabled) {
            background-color: rgba(0, 133, 80, 0.05);
        }

        .pagination-btn.active {
            background-color: #008550;
            color: white;
            border-color: #008550;
        }

        .pagination-btn.disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .info-box {
            margin-top: 2rem;
            padding: 1rem;
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 0.5rem;
        }
    </style>
@endsection

@section('content')
    <div class="content">
        <!-- Success/Error Messages -->
        @if(session('success'))
            <div
                class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg flex items-center justify-between mb-4">
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
                class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center justify-between mb-4">
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
        <div style="padding: 2rem;">
            <!-- Header Section -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem; margin-bottom: 2rem;">
                <div
                    style="display: flex; flex-direction: row; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 250px;">
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight"
                            style="font-size: 1.875rem; font-weight: 900; line-height: 1.2;">Framework Management</h1>
                        <p class="text-slate-500 mt-1" style="color: #64748b; margin-top: 0.25rem;">Initialize, manage,
                            and archive annual performance evaluation frameworks.</p>
                    </div>
                    <div style="flex-shrink: 0;">
                        <a href="{{ route('frameworks.create') }}"
                           class="inline-flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold py-2.5 px-6 rounded-lg shadow-sm transition-all"
                           style="background-color: #008550; color: white; padding: 0.625rem 1.5rem; border-radius: 0.5rem; box-shadow: 0 1px 2px 0 rgba(0, 133, 80, 0.2); display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; white-space: nowrap;">
                            <span class="material-symbols-outlined" style="font-size: 20px;">add_circle</span>
                            Initialize New Framework
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8"
                 style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 text-sm font-medium"
                              style="color: #64748b; font-size: 0.875rem; font-weight: 500;">Active Frameworks</span>
                        <span class="material-symbols-outlined text-primary" style="font-size: 24px; color: #008550;">rocket_launch</span>
                    </div>
                    <p class="text-2xl font-bold mt-2"
                       style="font-size: 1.5rem; font-weight: bold; margin-top: 0.5rem;">{{ $activeCount }}</p>
                    <div class="mt-2 text-xs text-primary font-medium flex items-center gap-1"
                         style="margin-top: 0.5rem; font-size: 0.75rem; color: #008550; font-weight: 500; display: flex; align-items: center; gap: 0.25rem;">
                        <span class="material-symbols-outlined" style="font-size: 14px;">calendar_today</span>
                        @if($currentYear)
                            Year {{ $currentYear }} Currently Open
                        @else
                            No Active Framework
                        @endif
                    </div>
                </div>
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 text-sm font-medium"
                              style="color: #64748b; font-size: 0.875rem; font-weight: 500;">Total Archived</span>
                        <span class="material-symbols-outlined text-slate-400" style="font-size: 24px; color: #94a3b8;">inventory_2</span>
                    </div>
                    <p class="text-2xl font-bold mt-2"
                       style="font-size: 1.5rem; font-weight: bold; margin-top: 0.5rem;">{{ $archivedCount }}</p>
                    <div class="mt-2 text-xs text-slate-400 font-medium"
                         style="margin-top: 0.5rem; font-size: 0.75rem; color: #94a3b8; font-weight: 500;">
                        @php
                            $lastArchived = \App\Models\Framework::where('status', 'Archived')
                                ->orderBy('archived_at', 'desc')
                                ->first();
                        @endphp
                        @if($lastArchived && $lastArchived->archived_at)
                            Last archived: {{ Carbon::parse($lastArchived->archived_at)->format('M Y') }}
                        @else
                            No archived frameworks
                        @endif
                    </div>
                </div>
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 text-sm font-medium"
                              style="color: #64748b; font-size: 0.875rem; font-weight: 500;">Avg. Completion</span>
                        <span class="material-symbols-outlined text-amber-500" style="font-size: 24px; color: #f59e0b;">trending_up</span>
                    </div>
                    <p class="text-2xl font-bold mt-2"
                       style="font-size: 1.5rem; font-weight: bold; margin-top: 0.5rem;">{{ $avgCompletion }}%</p>
                    <div class="mt-2 w-full bg-slate-100 rounded-full"
                         style="margin-top: 0.5rem; width: 100%; background-color: #f1f5f9; border-radius: 9999px; height: 6px;">
                        <div class="bg-amber-500 rounded-full"
                             style="background-color: #f59e0b; border-radius: 9999px; height: 6px; width: {{ $avgCompletion }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Framework Tabs -->
            <div class="mb-4 flex items-center gap-2 border-b border-primary/10"
                 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; border-bottom: 1px solid rgba(0, 133, 80, 0.1);">
                <a href="{{ route('frameworks.index', ['filter' => 'all']) }}"
                   class="px-6 py-3 text-sm {{ $filter === 'all' ? 'font-bold text-primary border-b-2 border-primary' : 'font-medium text-slate-500 hover:text-primary' }} transition-colors"
                   style="padding: 0.75rem 1.5rem; font-size: 0.875rem; {{ $filter === 'all' ? 'font-weight: bold; color: #008550; border-bottom: 2px solid #008550;' : 'font-weight: 500; color: #64748b;' }}">
                    All Frameworks
                </a>
                <a href="{{ route('frameworks.index', ['filter' => 'active']) }}"
                   class="px-6 py-3 text-sm {{ $filter === 'active' ? 'font-bold text-primary border-b-2 border-primary' : 'font-medium text-slate-500 hover:text-primary' }} transition-colors"
                   style="padding: 0.75rem 1.5rem; font-size: 0.875rem; {{ $filter === 'active' ? 'font-weight: bold; color: #008550; border-bottom: 2px solid #008550;' : 'font-weight: 500; color: #64748b;' }}">
                    Active Only
                </a>
                <a href="{{ route('frameworks.index', ['filter' => 'archived']) }}"
                   class="px-6 py-3 text-sm {{ $filter === 'archived' ? 'font-bold text-primary border-b-2 border-primary' : 'font-medium text-slate-500 hover:text-primary' }} transition-colors"
                   style="padding: 0.75rem 1.5rem; font-size: 0.875rem; {{ $filter === 'archived' ? 'font-weight: bold; color: #008550; border-bottom: 2px solid #008550;' : 'font-weight: 500; color: #64748b;' }}">
                    Archived
                </a>
            </div>

            <!-- Framework Table -->
            <div class="framework-table-container">
                <div class="overflow-x-auto">
                    <table class="framework-table w-full text-left" style="width: 100%; text-align: left;">
                        <thead>
                        <tr>
                            <th>Year</th>
                            <th>Framework Title</th>
                            <th>Status</th>
                            <th>Created On</th>
                            <th class="text-right">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-primary/5" style="border-top: 1px solid rgba(0, 133, 80, 0.1);">
                        @forelse($frameworks as $framework)
                            <tr class="{{ $framework->isActive() ? 'active-row' : '' }} hover:bg-primary/[0.02] transition-colors group"
                                style="{{ $framework->isActive() ? 'background-color: rgba(0, 133, 80, 0.05);' : '' }}">
                                <td>
                                    <span
                                        class="text-sm {{ $framework->isActive() ? 'font-bold text-slate-900' : 'font-medium text-slate-700' }}"
                                        style="font-size: 0.875rem; {{ $framework->isActive() ? 'font-weight: bold; color: #0f172a;' : 'font-weight: 500; color: #334155;' }}">{{ $framework->year }}</span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2"
                                         style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span
                                            class="text-sm {{ $framework->isActive() ? 'font-medium text-slate-900' : 'text-slate-700' }}"
                                            style="font-size: 0.875rem; {{ $framework->isActive() ? 'font-weight: 500; color: #0f172a;' : 'color: #334155;' }}">{{ $framework->title }}</span>
                                        @if($framework->isActive())
                                            <span class="material-symbols-outlined text-primary"
                                                  style="font-size: 18px; color: #008550;">verified</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($framework->isActive())
                                        <span class="status-badge-active">
                                            <span class="pulse-dot"></span>
                                            Active / Open
                                        </span>
                                    @else
                                        <span class="status-badge-archived">
                                            Closed / Archived
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-sm text-slate-500"
                                          style="font-size: 0.875rem; color: #64748b;">{{ Carbon::parse($framework->created_at)->format('M d, Y') }}</span>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-2"
                                         style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;">
                                        <a href="{{ route('frameworks.show', $framework) }}"
                                           class="px-3 py-1.5 text-xs font-bold {{ $framework->isActive() ? 'text-primary hover:bg-primary/10' : 'text-slate-600 hover:bg-slate-100' }} rounded transition-colors"
                                           style="padding: 0.375rem 0.75rem; font-size: 0.75rem; font-weight: bold; border-radius: 0.25rem; {{ $framework->isActive() ? 'color: #008550;' : 'color: #475569;' }}">
                                            View
                                        </a>
                                        @if($framework->isActive())
                                            <form action="{{ route('frameworks.archive', $framework) }}" method="POST"
                                                  class="inline">
                                                @csrf
                                                @method('POST')
                                                <button type="submit"
                                                        class="px-3 py-1.5 text-xs font-bold bg-primary text-white hover:bg-primary/90 rounded shadow-sm transition-all"
                                                        style="padding: 0.375rem 0.75rem; font-size: 0.75rem; font-weight: bold; background-color: #008550; color: white; border-radius: 0.25rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);"
                                                        onclick="return confirm('Are you sure you want to archive this framework?')">
                                                    Manage
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('frameworks.activate', $framework) }}" method="POST"
                                                  class="inline">
                                                @csrf
                                                @method('POST')
                                                <button type="submit"
                                                        class="px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded transition-colors"
                                                        style="padding: 0.375rem 0.75rem; font-size: 0.75rem; font-weight: bold; color: #475569; border-radius: 0.25rem;"
                                                        onclick="return confirm('Are you sure you want to activate this framework? This will archive the current active framework.')">
                                                    Manage
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-slate-500"
                                    style="text-align: center; padding: 2rem 0; color: #64748b;">
                                    No frameworks found.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                @if($frameworks->hasPages())
                    <div class="pagination-container flex items-center justify-between">
                        <p class="text-xs text-slate-500 font-medium tracking-wide"
                           style="font-size: 0.75rem; color: #64748b; font-weight: 500; letter-spacing: 0.05em;">
                            Showing {{ $frameworks->firstItem() }} to {{ $frameworks->lastItem() }}
                            of {{ $frameworks->total() }} Frameworks
                        </p>
                        <div class="flex items-center gap-1" style="display: flex; align-items: center; gap: 0.25rem;">
                            @if($frameworks->onFirstPage())
                                <button class="pagination-btn disabled" disabled
                                        style="cursor: not-allowed; opacity: 0.5;">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">chevron_left</span>
                                </button>
                            @else
                                <a href="{{ $frameworks->previousPageUrl() }}" class="pagination-btn">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">chevron_left</span>
                                </a>
                            @endif

                            @for($page = 1; $page <= $frameworks->lastPage(); $page++)
                                @if($page == $frameworks->currentPage())
                                    <span class="pagination-btn active"
                                          style="width: 2rem; height: 2rem; display: inline-flex; align-items: center; justify-content: center; background-color: #008550; color: white; border-color: #008550;">{{ $page }}</span>
                                @else
                                    <a href="{{ $frameworks->url($page) }}" class="pagination-btn"
                                       style="width: 2rem; height: 2rem; display: inline-flex; align-items: center; justify-content: center;">{{ $page }}</a>
                                @endif
                            @endfor

                            @if($frameworks->hasMorePages())
                                <a href="{{ $frameworks->nextPageUrl() }}" class="pagination-btn">
                                    <span class="material-symbols-outlined"
                                          style="font-size: 18px;">chevron_right</span>
                                </a>
                            @else
                                <button class="pagination-btn disabled" disabled
                                        style="cursor: not-allowed; opacity: 0.5;">
                                    <span class="material-symbols-outlined"
                                          style="font-size: 18px;">chevron_right</span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Context Info -->
            <div class="info-box flex items-start gap-4">
                <span class="material-symbols-outlined text-amber-600"
                      style="font-size: 24px; color: #d97706; flex-shrink: 0;">info</span>
                <div>
                    <h4 class="text-sm font-bold text-amber-800"
                        style="font-size: 0.875rem; font-weight: bold; color: #92400e;">Archived Frameworks Policy</h4>
                    <p class="text-sm text-amber-700 mt-1"
                       style="font-size: 0.875rem; color: #b45309; margin-top: 0.25rem;">Archived frameworks are
                        read-only. Modification requires high-level administrative bypass. Data integrity for historical
                        years is protected by PDCU system security protocols.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        // Add any JavaScript functionality here if needed
    </script>
@endsection
