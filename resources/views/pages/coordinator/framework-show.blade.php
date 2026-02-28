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

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cfe7db;
            border-radius: 10px;
        }

        .stat-card {
            background: white;
            padding: 1.25rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(0, 133, 80, 0.1);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon-blue {
            background-color: #dbeafe;
            color: #2563eb;
        }

        .stat-icon-amber {
            background-color: #fef3c7;
            color: #d97706;
        }

        .stat-icon-purple {
            background-color: #e9d5ff;
            color: #9333ea;
        }

        .stat-icon-primary {
            background-color: rgba(0, 133, 80, 0.1);
            color: #008550;
        }

        .status-badge {
            padding: 0.125rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: 1px solid;
        }

        .status-badge-active {
            background-color: rgba(0, 133, 80, 0.2);
            color: #008550;
            border-color: rgba(0, 133, 80, 0.3);
        }

        .structure-item {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            overflow: hidden;
        }

        .structure-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            cursor: pointer;
            transition: background-color 0.15s;
        }

        .structure-header:hover {
            background-color: #f1f5f9;
        }

        .structure-content {
            padding: 0.75rem;
            background-color: white;
        }

        .commitment-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background-color: white;
            border: 1px solid #f1f5f9;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            margin-left: 2rem;
        }

        .deliverable-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem;
            background-color: #f8fafc;
            border: 1px solid transparent;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
            margin-left: 2rem;
        }

        .kpi-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem;
            margin-bottom: 0.25rem;
            margin-left: 2rem;
        }

        .progress-bar {
            width: 4rem;
            height: 0.375rem;
            background-color: #e2e8f0;
            border-radius: 9999px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background-color: #008550;
        }

        .main-content-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            align-items: flex-start;
        }

        .main-content-area {
            flex: 1;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .sidebar-area {
            width: 100%;
            max-width: 20rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        @media (min-width: 1024px) {
            .main-content-wrapper {
                flex-direction: row;
            }

            .main-content-area {
                flex: 1;
            }

            .sidebar-area {
                width: 20rem;
                max-width: 20rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="content">
        <!-- Breadcrumbs -->
        <nav
            style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #64748b; margin-bottom: 1.5rem;">
            <a href="{{ route('dashboard') }}" style="color: #008550; text-decoration: underline;">Home</a>
            <span class="material-symbols-outlined" style="font-size: 0.875rem;">chevron_right</span>
            <a href="{{ route('frameworks.index') }}" style="color: #008550; text-decoration: underline;">Frameworks</a>
            <span class="material-symbols-outlined" style="font-size: 0.875rem;">chevron_right</span>
            <span style="color: #0f172a; font-weight: 500;">{{ $framework->title }}</span>
        </nav>

        <!-- Header Card -->
        <div
            style="display: flex; flex-direction: column; gap: 1rem; background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid rgba(0, 133, 80, 0.1); box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); margin-bottom: 1.5rem;">
            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <h1 style="font-size: 1.5rem; font-weight: 900; color: #0f172a; margin: 0;">{{ $framework->title }}</h1>
                    <span
                        class="status-badge {{ $framework->isActive() ? 'status-badge-active' : 'status-badge-archived' }}">
                        {{ $framework->isActive() ? 'Open' : 'Archived' }}
                    </span>
                </div>
                <p style="color: #64748b; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                    <span class="material-symbols-outlined" style="font-size: 1rem;">calendar_today</span>
                    Created on {{ Carbon::parse($framework->created_at)->format('M d, Y') }}
                    @if($framework->creator)
                        • Managed by {{ $framework->creator->name }}
                    @endif
                </p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                @if($framework->isActive())
                    <form action="{{ route('frameworks.archive', $framework) }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit"
                                style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background-color: rgba(0, 133, 80, 0.1); color: #008550; border: none; border-radius: 0.5rem; font-weight: bold; font-size: 0.875rem; cursor: pointer; transition: all 0.15s;"
                                onmouseover="this.style.backgroundColor='rgba(0, 133, 80, 0.2)'"
                                onmouseout="this.style.backgroundColor='rgba(0, 133, 80, 0.1)'"
                                onclick="return confirm('Are you sure you want to archive this framework?')">
                            <span class="material-symbols-outlined" style="font-size: 1rem;">lock</span>
                            Archive Framework
                        </button>
                    </form>
                @else
                    <form action="{{ route('frameworks.activate', $framework) }}" method="POST"
                          style="display: inline;">
                        @csrf
                        <button type="submit"
                                style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background-color: #008550; color: white; border: none; border-radius: 0.5rem; font-weight: bold; font-size: 0.875rem; cursor: pointer; transition: all 0.15s; box-shadow: 0 4px 6px -1px rgba(0, 133, 80, 0.2);"
                                onmouseover="this.style.backgroundColor='rgba(0, 133, 80, 0.9)'"
                                onmouseout="this.style.backgroundColor='#008550'"
                                onclick="return confirm('Are you sure you want to activate this framework? This will archive the current active framework.')">
                            <span class="material-symbols-outlined" style="font-size: 1rem;">publish</span>
                            Activate Framework
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Stat Cards -->
        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="stat-card">
                <div class="stat-icon stat-icon-blue">
                    <span class="material-symbols-outlined">category</span>
                </div>
                <div>
                    <p style="color: #64748b; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 0.25rem 0;">
                        Sectors</p>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #0f172a; margin: 0;">{{ $sectorsCount }}</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-amber">
                    <span class="material-symbols-outlined">handshake</span>
                </div>
                <div>
                    <p style="color: #64748b; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 0.25rem 0;">
                        Commitments</p>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #0f172a; margin: 0;">{{ $commitmentsCount }}</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-purple">
                    <span class="material-symbols-outlined">package_2</span>
                </div>
                <div>
                    <p style="color: #64748b; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 0.25rem 0;">
                        Deliverables</p>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #0f172a; margin: 0;">{{ $deliverablesCount }}</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-primary">
                    <span class="material-symbols-outlined">monitoring</span>
                </div>
                <div>
                    <p style="color: #64748b; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 0.25rem 0;">
                        KPIs</p>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #0f172a; margin: 0;">{{ $kpisCount }}</p>
                </div>
            </div>
        </div>

        <!-- Main Content with Sidebar -->
        <div class="main-content-wrapper">
            <!-- Main Content - Structure Navigator -->
            <div class="main-content-area">
                <!-- Structure Navigator -->
                <div
                    style="background: white; border-radius: 0.75rem; border: 1px solid rgba(0, 133, 80, 0.1); overflow: hidden; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                    <div
                        style="padding: 1rem 1.5rem; border-bottom: 1px solid rgba(0, 133, 80, 0.05); background-color: rgba(248, 250, 252, 0.5); display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="font-weight: bold; color: #0f172a; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                            <span class="material-symbols-outlined" style="color: #008550;">account_tree</span>
                            Structure Navigator
                        </h3>
                        <div style="display: flex; gap: 0.5rem;">
                            <button onclick="expandAll()"
                                    style="font-size: 0.75rem; font-weight: 500; color: #64748b; padding: 0.25rem 0.5rem; border: none; background: none; cursor: pointer; border-radius: 0.25rem;"
                                    onmouseover="this.style.color='#008550'" onmouseout="this.style.color='#64748b'">
                                Expand All
                            </button>
                            <button onclick="collapseAll()"
                                    style="font-size: 0.75rem; font-weight: 500; color: #64748b; padding: 0.25rem 0.5rem; border: none; background: none; cursor: pointer; border-radius: 0.25rem;"
                                    onmouseover="this.style.color='#008550'" onmouseout="this.style.color='#64748b'">
                                Collapse All
                            </button>
                        </div>
                    </div>
                    <div style="padding: 1.5rem;">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            @forelse($framework->sectors as $sector)
                                <details class="structure-item" data-sector="{{ $sector->id }}">
                                    <summary class="structure-header">
                                        <span class="material-symbols-outlined"
                                              style="color: #94a3b8;">expand_more</span>
                                        <div
                                            style="width: 2rem; height: 2rem; border-radius: 0.5rem; background-color: #3b82f6; display: flex; align-items: center; justify-content: center; color: white;">
                                            <span class="material-symbols-outlined" style="font-size: 1rem;">health_and_safety</span>
                                        </div>
                                        <div style="flex: 1;">
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <p style="font-weight: bold; color: #1e293b; margin: 0;">{{ $sector->sector_name }}</p>
                                                <span
                                                    style="font-size: 0.625rem; padding: 0.125rem 0.375rem; background-color: #dbeafe; color: #1e40af; border-radius: 9999px; font-weight: bold;">SECTOR</span>
                                            </div>
                                        </div>
                                    </summary>
                                    <div class="structure-content">
                                        @forelse($sector->commitments as $commitment)
                                            <details style="margin-bottom: 0.75rem;">
                                                <summary
                                                    style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background-color: white; border: 1px solid #f1f5f9; border-radius: 0.5rem; cursor: pointer; list-style: none;">
                                                    <span class="material-symbols-outlined"
                                                          style="color: #94a3b8; font-size: 1rem;">expand_more</span>
                                                    <div
                                                        style="width: 1.75rem; height: 1.75rem; border-radius: 0.375rem; background-color: #f59e0b; display: flex; align-items: center; justify-content: center; color: white;">
                                                        <span class="material-symbols-outlined"
                                                              style="font-size: 0.875rem;">handshake</span>
                                                    </div>
                                                    <div style="flex: 1;">
                                                        <p style="font-size: 0.875rem; font-weight: 600; color: #334155; margin: 0;">{{ $commitment->name }}</p>
                                                    </div>
                                                    <span
                                                        style="font-size: 0.625rem; font-weight: bold; color: #94a3b8;">{{ $commitment->deliverables->count() }} Deliverables</span>
                                                </summary>
                                                <div
                                                    style="margin-left: 2rem; margin-top: 0.5rem; padding-left: 1.5rem; border-left: 2px solid #f1f5f9;">
                                                    @forelse($commitment->deliverables as $deliverable)
                                                        <details style="margin-bottom: 0.5rem;">
                                                            <summary
                                                                style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem; background-color: #f8fafc; border: 1px solid transparent; border-radius: 0.375rem; cursor: pointer; list-style: none;">
                                                                <span class="material-symbols-outlined"
                                                                      style="color: #cbd5e1; font-size: 0.875rem;">expand_more</span>
                                                                <span class="material-symbols-outlined"
                                                                      style="color: #9333ea; font-size: 1rem;">package_2</span>
                                                                <p style="font-size: 0.75rem; font-weight: 500; color: #475569; flex: 1; margin: 0;">{{ $deliverable->deliverable }}</p>
                                                                <div
                                                                    style="display: flex; align-items: center; gap: 1rem;">
                                                                    <div class="progress-bar">
                                                                        <div class="progress-fill"
                                                                             style="width: 75%;"></div>
                                                                    </div>
                                                                    <span
                                                                        style="font-size: 0.625rem; font-weight: bold; color: #008550;">75%</span>
                                                                </div>
                                                            </summary>
                                                            <div style="margin-left: 2rem; margin-top: 0.25rem;">
                                                                @forelse($deliverable->kpis as $kpi)
                                                                    <div class="kpi-item"
                                                                         style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem; transition: background-color 0.15s;"
                                                                         onmouseover="this.style.backgroundColor='white'"
                                                                         onmouseout="this.style.backgroundColor='transparent'">
                                                                        <span class="material-symbols-outlined"
                                                                              style="color: #008550; font-size: 0.875rem;">monitoring</span>
                                                                        <p style="font-size: 0.75rem; color: #475569; flex: 1; margin: 0; text-decoration: underline; text-decoration-style: dotted; text-underline-offset: 4px;">{{ $kpi->kpi }}</p>
                                                                    </div>
                                                                @empty
                                                                    <p style="font-size: 0.75rem; color: #94a3b8; font-style: italic; margin-left: 2rem;">
                                                                        No KPIs</p>
                                                                @endforelse
                                                            </div>
                                                        </details>
                                                    @empty
                                                        <p style="font-size: 0.75rem; color: #94a3b8; font-style: italic; margin-left: 2rem;">
                                                            No deliverables</p>
                                                    @endforelse
                                                </div>
                                            </details>
                                        @empty
                                            <p style="font-size: 0.75rem; color: #94a3b8; font-style: italic; margin-left: 2rem;">
                                                No commitments</p>
                                        @endforelse
                                    </div>
                                </details>
                            @empty
                                <p style="text-align: center; color: #94a3b8; padding: 2rem;">No sectors found in this
                                    framework.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="sidebar-area">
                <!-- Metadata -->
                <div
                    style="background: white; border-radius: 0.75rem; border: 1px solid rgba(0, 133, 80, 0.1); overflow: hidden; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                    <div
                        style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(0, 133, 80, 0.05); background-color: rgba(248, 250, 252, 0.5);">
                        <h3 style="font-weight: bold; color: #0f172a; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                            <span class="material-symbols-outlined"
                                  style="color: #008550; font-size: 1.125rem;">info</span>
                            Metadata
                        </h3>
                    </div>
                    <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <p style="font-size: 0.625rem; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 0.25rem 0;">
                                Fiscal Year</p>
                            <p style="font-size: 0.875rem; font-weight: 600; color: #334155; margin: 0;">{{ $framework->year }}</p>
                        </div>
                        <div>
                            <p style="font-size: 0.625rem; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 0.25rem 0;">
                                Lead Agency</p>
                            <p style="font-size: 0.875rem; font-weight: 600; color: #334155; margin: 0;">PDCU
                                Performance Delivery Coordination Unit</p>
                        </div>
                        <div>
                            <p style="font-size: 0.625rem; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 0.25rem 0;">
                                Framework Type</p>
                            <p style="font-size: 0.875rem; font-weight: 600; color: #334155; margin: 0;">Annual
                                Performance Plan</p>
                        </div>
                        <hr style="border-color: #f1f5f9; margin: 0.5rem 0;"/>
                        @if($framework->isActive())
                            <form action="{{ route('frameworks.archive', $framework) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem; border-radius: 0.5rem; border: 2px solid rgba(239, 68, 68, 0.2); color: #ef4444; background: none; font-weight: bold; font-size: 0.875rem; cursor: pointer; transition: all 0.15s;"
                                        onmouseover="this.style.backgroundColor='#ef4444'; this.style.color='white'"
                                        onmouseout="this.style.backgroundColor='transparent'; this.style.color='#ef4444'"
                                        onclick="return confirm('Are you sure you want to archive this framework?')">
                                    <span class="material-symbols-outlined" style="font-size: 1.125rem;">lock</span>
                                    Close Framework
                                </button>
                            </form>
                            <p style="font-size: 0.625rem; text-align: center; color: #94a3b8; margin: 0.5rem 0 0 0;">
                                Closing prevents further modifications to the structure.</p>
                        @endif
                    </div>
                </div>

                <!-- Audit Logs -->
                <div
                    style="background: white; border-radius: 0.75rem; border: 1px solid rgba(0, 133, 80, 0.1); overflow: hidden; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                    <div
                        style="padding: 1rem 1.25rem; border-bottom: 1px solid rgba(0, 133, 80, 0.05); background-color: rgba(248, 250, 252, 0.5);">
                        <h3 style="font-weight: bold; color: #0f172a; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                            <span class="material-symbols-outlined"
                                  style="color: #008550; font-size: 1.125rem;">history</span>
                            Audit Logs
                        </h3>
                    </div>
                    <div style="padding: 1.25rem; max-height: 20rem; overflow-y: auto; custom-scrollbar">
                        <div style="position: relative; padding-left: 1.5rem;">
                            <div
                                style="position: absolute; left: 0.5rem; top: 0.5rem; bottom: 0.5rem; width: 2px; background-color: #f1f5f9;"></div>
                            <div style="position: relative; padding-bottom: 1rem;">
                                <div
                                    style="position: absolute; left: -0.5rem; top: 0.375rem; width: 1rem; height: 1rem; border-radius: 50%; border: 2px solid #008550; background-color: white;"></div>
                                <p style="font-size: 0.75rem; font-weight: bold; color: #1e293b; margin: 0;">Framework
                                    Initialized</p>
                                <p style="font-size: 0.625rem; color: #94a3b8; margin: 0.125rem 0 0 0;">{{ Carbon::parse($framework->created_at)->format('M d, Y') }}
                                    • by {{ $framework->creator->name ?? 'System' }}</p>
                            </div>
                            @if($framework->isArchived() && $framework->archived_at)
                                <div style="position: relative; padding-bottom: 1rem;">
                                    <div
                                        style="position: absolute; left: -0.5rem; top: 0.375rem; width: 1rem; height: 1rem; border-radius: 50%; border: 2px solid #e2e8f0; background-color: white;"></div>
                                    <p style="font-size: 0.75rem; font-weight: bold; color: #1e293b; margin: 0;">
                                        Framework Archived</p>
                                    <p style="font-size: 0.625rem; color: #94a3b8; margin: 0.125rem 0 0 0;">{{ Carbon::parse($framework->archived_at)->format('M d, Y') }}
                                        • by {{ $framework->archiver->name ?? 'System' }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Export Options -->
                {{--                <div style="background-color: rgba(0, 133, 80, 0.05); border-radius: 0.75rem; border: 1px solid rgba(0, 133, 80, 0.2); padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;">--}}
                {{--                    <h4 style="font-size: 0.75rem; font-weight: 900; color: #008550; text-transform: uppercase; letter-spacing: 0.1em; margin: 0;">Export Options</h4>--}}
                {{--                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem;">--}}
                {{--                        <button style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.5rem; background: white; border-radius: 0.375rem; border: 1px solid rgba(0, 133, 80, 0.1); cursor: pointer; transition: all 0.15s;" onmouseover="this.style.borderColor='#008550'" onmouseout="this.style.borderColor='rgba(0, 133, 80, 0.1)'">--}}
                {{--                            <span class="material-symbols-outlined" style="font-size: 1.125rem; color: #94a3b8;">picture_as_pdf</span>--}}
                {{--                            <span style="font-size: 0.625rem; font-weight: bold;">PDF</span>--}}
                {{--                        </button>--}}
                {{--                        <button style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.5rem; background: white; border-radius: 0.375rem; border: 1px solid rgba(0, 133, 80, 0.1); cursor: pointer; transition: all 0.15s;" onmouseover="this.style.borderColor='#008550'" onmouseout="this.style.borderColor='rgba(0, 133, 80, 0.1)'">--}}
                {{--                            <span class="material-symbols-outlined" style="font-size: 1.125rem; color: #94a3b8;">table_view</span>--}}
                {{--                            <span style="font-size: 0.625rem; font-weight: bold;">Excel</span>--}}
                {{--                        </button>--}}
                {{--                    </div>--}}
                {{--                </div>--}}
            </aside>
        </div>
    </div>
@endsection

@section('js')
    <script>
        function expandAll() {
            document.querySelectorAll('details[data-sector]').forEach(detail => {
                detail.open = true;
            });
            document.querySelectorAll('details[data-sector] details').forEach(detail => {
                detail.open = true;
            });
        }

        function collapseAll() {
            document.querySelectorAll('details[data-sector]').forEach(detail => {
                detail.open = false;
            });
            document.querySelectorAll('details[data-sector] details').forEach(detail => {
                detail.open = false;
            });
        }
    </script>
@endsection
