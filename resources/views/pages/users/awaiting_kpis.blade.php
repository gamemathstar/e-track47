@php use Carbon\Carbon; @endphp
@extends('layouts.app')

@section('content')
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium ml-3 mr-auto">Deliverable</h2>
    </div>
    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12 lg:col-span-12 2xl:col-span-12">
            <div class="box p-5 rounded-md">
                <div class="flex items-center border-slate-200/60 dark:border-darkmode-400">
                    <div class="text-primary text-2xl">{{ $deliverable->deliverable }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12 lg:col-span-12 2xl:col-span-12">
            <div class="rounded-md">
                {{--                <a href="javascript:;" class="btn btn-primary ml-3" data-tw-toggle="modal"--}}
                {{--                   data-tw-target="#header-footer-modal-preview">--}}
                {{--                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"--}}
                {{--                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"--}}
                {{--                         icon-name="edit" data-lucide="edit" class="lucide lucide-edit w-4 h-4 mr-2">--}}
                {{--                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>--}}
                {{--                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>--}}
                {{--                    </svg>--}}
                {{--                    Add New--}}
                {{--                </a>--}}
                @if($kpis->count())
                    <table class="table table-bordered table-report mt-2">
                        <thead>
                        <tr>
                            <th class="whitespace-nowrap">#</th>
                            <th class="whitespace-nowrap">KPI</th>
                            <th class="whitespace-nowrap">Target</th>
                            <th class="whitespace-nowrap">Year</th>
                            <th class="whitespace-nowrap">1<sup>st</sup> QPT</th>
                            <th class="whitespace-nowrap">2<sup>nd</sup> QPT</th>
                            <th class="whitespace-nowrap">3<sup>rd</sup> QPT</th>
                            <th class="whitespace-nowrap">4<sup>th</sup> QPT</th>
                            {{--                            <th class="text-center whitespace-nowrap">Action</th>--}}
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($kpis as $kpi)
                            @php $tracks = $kpi->performanceTracking()->get(); @endphp
                            <tr>
                                <td>
                                    {{ $loop->iteration }}
                                </td>
                                <td>
                                    {{ $kpi->kpi }}
                                </td>
                                <td>{{ $kpi->target_value }} ({{ $kpi->unit_of_measurement }})</td>
                                <td>{{ $kpi->year ?? '---' }}</td>
                                <td>
                                    @php
                                        $q1Track = $tracks->where('quarter', 1)->first();
                                    @endphp
                                    @if($q1Track && $q1Track->sector_head_approved_by)
                                        @if($q1Track->actual_value && $q1Track->sector_head_approved_by)
                                            {{-- Show actual_value clickable for Facilitators, with plus button if conditions are met --}}
                                            <div class="flex items-center justify-center gap-2">
                                                @if($user && $user->isFacilitator())
                                                    {{-- For Facilitators: actual_value is clickable to view details --}}
                                                    <a href="javascript:;"
                                                       class="view-facilitator {{ $q1Track->facilitator_decision == 'Reject' ? 'text-danger' : 'text-primary' }} hover:underline cursor-pointer"
                                                       data-tw-toggle="modal"
                                                       data-tw-target="#view-performance"
                                                       data-id="{{ $q1Track->id }}"
                                                       data-kpi="{{ $kpi->kpi }}"
                                                       data-kpi-id="{{ $kpi->id }}"
                                                       data-qt="{{ $q1Track->quarter == 1 ? '1st' : ($q1Track->quarter == 2 ? '2nd' : ($q1Track->quarter == 3 ? '3rd' : '4th')) }} QT">
                                                        {{ $q1Track->actual_value }}
                                                    </a>
                                                @else
                                                    <span>{{ $q1Track->actual_value }}</span>
                                                @endif
                                                @if(($user && $user->isFacilitator()) && $q1Track->actual_value && $q1Track->actual_value != 0 && $q1Track->sector_head_approved_by && !$q1Track->coordinator_confirmed_by && !$q1Track->facilitator_confirmed_by)
                                                    <a href="javascript:"
                                                       class="add-delivery-dept inline-flex items-center text-emerald-600 hover:text-emerald-700"
                                                       data-tw-toggle="modal"
                                                       data-track-id="{{ $q1Track->id }}"
                                                       data-kpi-id="{{ $kpi->id }}"
                                                       data-kpi="{{ $kpi->kpi }}"
                                                       data-quarter="{{ $q1Track->quarter }}"
                                                       data-year="{{ $q1Track->year }}"
                                                       data-tracking-date="{{ $q1Track->tracking_date ? \Carbon\Carbon::parse($q1Track->tracking_date)->format('Y-m-d') : '' }}"
                                                       data-milestone="{{ $q1Track->milestone }}"
                                                       data-actual-value="{{ $q1Track->actual_value }}"
                                                       data-remarks="{{ $q1Track->remarks }}"
                                                       data-tw-target="#add-delivery-dept-modal"
                                                       title="Add Delivery Department Value">
                                                        <i data-lucide="check" class="w-5 h-5"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        @elseif($q1Track->confirmation_status != 'Not Confirmed')
                                            <a href="javascript:;" data-tw-toggle="modal"
                                               data-tw-target="#view-performance"
                                               data-id="{{ $q1Track->id }}" data-kpi="{{ $kpi->kpi }}"
                                               data-kpi-id="{{$kpi->id}}" data-qt="1st QT"
                                               class="view text-{{ $q1Track->confirmation_status=='Confirmed'?'success':($q1Track->confirmation_status=='Rejected'?'danger':'') }} block">
                                                {{ $q1Track->actual_value ?? '-' }}
                                            </a>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $q2Track = $tracks->where('quarter', 2)->first();
                                    @endphp
                                    @if($q2Track && $q2Track->sector_head_approved_by)
                                        @if($q2Track->actual_value && $q2Track->sector_head_approved_by)
                                            {{-- Show actual_value clickable for Facilitators, with plus button if conditions are met --}}
                                            <div class="flex items-center justify-center gap-2">
                                                @if($user && $user->isFacilitator())
                                                    {{-- For Facilitators: actual_value is clickable to view details --}}
                                                    <a href="javascript:;"
                                                       class="view-facilitator {{ $q2Track->facilitator_decision == 'Reject' ? 'text-danger' : 'text-primary' }} hover:underline cursor-pointer"
                                                       data-tw-toggle="modal"
                                                       data-tw-target="#view-performance"
                                                       data-id="{{ $q2Track->id }}"
                                                       data-kpi="{{ $kpi->kpi }}"
                                                       data-kpi-id="{{ $kpi->id }}"
                                                       data-qt="{{ $q2Track->quarter == 1 ? '1st' : ($q2Track->quarter == 2 ? '2nd' : ($q2Track->quarter == 3 ? '3rd' : '4th')) }} QT">
                                                        {{ $q2Track->actual_value }}
                                                    </a>
                                                @else
                                                    <span>{{ $q2Track->actual_value }}</span>
                                                @endif
                                                @if(($user && $user->isFacilitator()) && $q2Track->actual_value && $q2Track->actual_value != 0 && $q2Track->sector_head_approved_by && !$q2Track->coordinator_confirmed_by && !$q2Track->facilitator_confirmed_by)
                                                    <a href="javascript:"
                                                       class="add-delivery-dept inline-flex items-center text-emerald-600 hover:text-emerald-700"
                                                       data-tw-toggle="modal"
                                                       data-track-id="{{ $q2Track->id }}"
                                                       data-kpi-id="{{ $kpi->id }}"
                                                       data-kpi="{{ $kpi->kpi }}"
                                                       data-quarter="{{ $q2Track->quarter }}"
                                                       data-year="{{ $q2Track->year }}"
                                                       data-tracking-date="{{ $q2Track->tracking_date ? \Carbon\Carbon::parse($q2Track->tracking_date)->format('Y-m-d') : '' }}"
                                                       data-milestone="{{ $q2Track->milestone }}"
                                                       data-actual-value="{{ $q2Track->actual_value }}"
                                                       data-remarks="{{ $q2Track->remarks }}"
                                                       data-tw-target="#add-delivery-dept-modal"
                                                       title="Add Delivery Department Value">
                                                        <i data-lucide="check" class="w-4 h-4"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        @elseif($q2Track->confirmation_status != 'Not Confirmed')
                                            <a href="javascript:;" data-tw-toggle="modal"
                                               data-tw-target="#view-performance"
                                               data-id="{{ $q2Track->id }}" data-kpi="{{ $kpi->kpi }}"
                                               data-kpi-id="{{$kpi->id}}" data-qt="2nd QT"
                                               class="view text-{{ $q2Track->confirmation_status=='Confirmed'?'success':($q2Track->confirmation_status=='Rejected'?'danger':'') }} block">
                                                {{ $q2Track->actual_value ?? '-' }}
                                            </a>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $q3Track = $tracks->where('quarter', 3)->first();
                                    @endphp
                                    @if($q3Track && $q3Track->sector_head_approved_by)
                                        @if($q3Track->actual_value && $q3Track->sector_head_approved_by)
                                            {{-- Show actual_value clickable for Facilitators, with plus button if conditions are met --}}
                                            <div class="flex items-center justify-center gap-2">
                                                @if($user && $user->isFacilitator())
                                                    {{-- For Facilitators: actual_value is clickable to view details --}}
                                                    <a href="javascript:;"
                                                       class="view-facilitator {{ $q3Track->facilitator_decision == 'Reject' ? 'text-danger' : 'text-primary' }} hover:underline cursor-pointer"
                                                       data-tw-toggle="modal"
                                                       data-tw-target="#view-performance"
                                                       data-id="{{ $q3Track->id }}"
                                                       data-kpi="{{ $kpi->kpi }}"
                                                       data-kpi-id="{{ $kpi->id }}"
                                                       data-qt="{{ $q3Track->quarter == 1 ? '1st' : ($q3Track->quarter == 2 ? '2nd' : ($q3Track->quarter == 3 ? '3rd' : '4th')) }} QT">
                                                        {{ $q3Track->actual_value }}
                                                    </a>
                                                @else
                                                    <span>{{ $q3Track->actual_value }}</span>
                                                @endif
                                                @if(($user && $user->isFacilitator()) && $q3Track->actual_value && $q3Track->actual_value != 0 && $q3Track->sector_head_approved_by && !$q3Track->coordinator_confirmed_by && !$q3Track->facilitator_confirmed_by)
                                                    <a href="javascript:"
                                                       class="add-delivery-dept inline-flex items-center text-emerald-600 hover:text-emerald-700"
                                                       data-tw-toggle="modal"
                                                       data-track-id="{{ $q3Track->id }}"
                                                       data-kpi-id="{{ $kpi->id }}"
                                                       data-kpi="{{ $kpi->kpi }}"
                                                       data-quarter="{{ $q3Track->quarter }}"
                                                       data-year="{{ $q3Track->year }}"
                                                       data-tracking-date="{{ $q3Track->tracking_date ? \Carbon\Carbon::parse($q3Track->tracking_date)->format('Y-m-d') : '' }}"
                                                       data-milestone="{{ $q3Track->milestone }}"
                                                       data-actual-value="{{ $q3Track->actual_value }}"
                                                       data-remarks="{{ $q3Track->remarks }}"
                                                       data-tw-target="#add-delivery-dept-modal"
                                                       title="Add Delivery Department Value">
                                                        <i data-lucide="check" class="w-4 h-4"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        @elseif($q3Track->confirmation_status != 'Not Confirmed')
                                            <a href="javascript:;" data-tw-toggle="modal"
                                               data-tw-target="#view-performance"
                                               data-id="{{ $q3Track->id }}" data-kpi="{{ $kpi->kpi }}"
                                               data-kpi-id="{{$kpi->id}}" data-qt="3rd QT"
                                               class="view text-{{ $q3Track->confirmation_status=='Confirmed'?'success':($q3Track->confirmation_status=='Rejected'?'danger':'') }} block">
                                                {{ $q3Track->actual_value ?? '-' }}
                                            </a>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $q4Track = $tracks->where('quarter', 4)->first();
                                    @endphp
                                    @if($q4Track && $q4Track->sector_head_approved_by)
                                        @if($q4Track->actual_value && $q4Track->sector_head_approved_by)
                                            {{-- Show actual_value clickable for Facilitators, with plus button if conditions are met --}}
                                            <div class="flex items-center justify-center gap-2">
                                                @if($user && $user->isFacilitator())
                                                    {{-- For Facilitators: actual_value is clickable to view details --}}
                                                    <a href="javascript:;"
                                                       class="view-facilitator {{ $q4Track->facilitator_decision == 'Reject' ? 'text-danger' : 'text-primary' }} hover:underline cursor-pointer"
                                                       data-tw-toggle="modal"
                                                       data-tw-target="#view-performance"
                                                       data-id="{{ $q4Track->id }}"
                                                       data-kpi="{{ $kpi->kpi }}"
                                                       data-kpi-id="{{ $kpi->id }}"
                                                       data-qt="{{ $q4Track->quarter == 1 ? '1st' : ($q4Track->quarter == 2 ? '2nd' : ($q4Track->quarter == 3 ? '3rd' : '4th')) }} QT">
                                                        {{ $q4Track->actual_value }}
                                                    </a>
                                                @else
                                                    <span>{{ $q4Track->actual_value }}</span>
                                                @endif
                                                @if(($user && $user->isFacilitator()) && $q4Track->actual_value && $q4Track->actual_value != 0 && $q4Track->sector_head_approved_by && !$q4Track->coordinator_confirmed_by && !$q4Track->facilitator_confirmed_by)
                                                    <a href="javascript:"
                                                       class="add-delivery-dept inline-flex items-center text-emerald-600 hover:text-emerald-700"
                                                       data-tw-toggle="modal"
                                                       data-track-id="{{ $q4Track->id }}"
                                                       data-kpi-id="{{ $kpi->id }}"
                                                       data-kpi="{{ $kpi->kpi }}"
                                                       data-quarter="{{ $q4Track->quarter }}"
                                                       data-year="{{ $q4Track->year }}"
                                                       data-tracking-date="{{ $q4Track->tracking_date ? \Carbon\Carbon::parse($q4Track->tracking_date)->format('Y-m-d') : '' }}"
                                                       data-milestone="{{ $q4Track->milestone }}"
                                                       data-actual-value="{{ $q4Track->actual_value }}"
                                                       data-remarks="{{ $q4Track->remarks }}"
                                                       data-tw-target="#add-delivery-dept-modal"
                                                       title="Add Delivery Department Value">
                                                        <i data-lucide="check" class="w-4 h-4"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        @elseif($q4Track->confirmation_status != 'Not Confirmed')
                                            <a href="javascript:;" data-tw-toggle="modal"
                                               data-tw-target="#view-performance"
                                               data-id="{{ $q4Track->id }}" data-kpi="{{ $kpi->kpi }}"
                                               data-kpi-id="{{$kpi->id}}" data-qt="4th QT"
                                               class="view text-{{ $q4Track->confirmation_status=='Confirmed'?'success':($q4Track->confirmation_status=='Rejected'?'danger':'') }} block">
                                                {{ $q4Track->actual_value ?? '-' }}
                                            </a>
                                        @endif
                                    @endif
                                </td>
                                {{--                                <td>--}}
                                {{--                                    <div class="flex justify-center items-center">--}}
                                {{--                                        <a class="flex items-center text-danger tooltip" data-theme="dark"--}}
                                {{--                                           title="Delete KPI" href="javascript:;" data-tw-toggle="modal"--}}
                                {{--                                           data-tw-target="#delete-modal-preview{{ $kpi->id }}">--}}
                                {{--                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"--}}
                                {{--                                                 viewBox="0 0 24 24"--}}
                                {{--                                                 fill="none" stroke="currentColor" stroke-width="2"--}}
                                {{--                                                 stroke-linecap="round"--}}
                                {{--                                                 stroke-linejoin="round" icon-name="trash-2" data-lucide="trash-2"--}}
                                {{--                                                 class="lucide lucide-trash-2 w-4 h-4 mr-1">--}}
                                {{--                                                <polyline points="3 6 5 6 21 6"></polyline>--}}
                                {{--                                                <path--}}
                                {{--                                                    d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>--}}
                                {{--                                                <line x1="10" y1="11" x2="10" y2="17"></line>--}}
                                {{--                                                <line x1="14" y1="11" x2="14" y2="17"></line>--}}
                                {{--                                            </svg>--}}
                                {{--                                        </a>--}}
                                {{--                                    </div>--}}
                                {{--                                </td>--}}
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <center>
                        Click <em class="text-success">Add New </em> to add deliverable.
                    </center>
                @endif

                <div id="header-footer-modal-preview" class="modal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form action="{{route('deliverable.add.kpi')}}" method="post">
                                @csrf
                                <input type="hidden" name="deliverable_id" value="{{$deliverable->id}}">
                                <!-- BEGIN: Modal Header -->
                                <div class="modal-header">
                                    <h2 class="font-medium text-base mr-auto">Add KPI
                                        to {{$deliverable->deliverable}}</h2>

                                </div> <!-- END: Modal Header -->
                                <!-- BEGIN: Modal Body -->
                                <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                                    <div class="col-span-12 sm:col-span-12">
                                        <label for="modal-form-1" class="form-label">KPI</label>
                                        <input id="modal-form-1" type="text" class="form-control"
                                               name="kpi" required>
                                    </div>
                                    <div class="col-span-6 sm:col-span-6">
                                        <label for="modal-form-1" class="form-label">Target Value</label>
                                        <input id="modal-form-1" type="number" class="form-control"
                                               name="target_value" step="any" required>
                                    </div>
                                    <div class="col-span-6 sm:col-span-6">
                                        <label for="modal-form-1" class="form-label">Unit of Measurement</label>
                                        <input id="modal-form-1" type="text" class="form-control"
                                               name="unit_of_measurement" required>
                                    </div>
                                    <div class="col-span-6 sm:col-span-6">
                                        <label for="modal-form-1" class="form-label">Year</label>
                                        <input id="modal-form-1" type="number" class="form-control"
                                               name="year" min="2000" max="2100" value="{{ date('Y') }}" required>
                                    </div>
                                </div> <!-- END: Modal Body -->
                                <!-- BEGIN: Modal Footer -->
                                <div class="modal-footer">
                                    <button type="button" data-tw-dismiss="modal"
                                            class="btn btn-outline-secondary w-20 mr-1">Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary w-20">Save</button>
                                </div> <!-- END: Modal Footer -->
                            </form>
                        </div>
                    </div>
                </div> <!-- END: Modal Content -->

                <div id="add-performance" class="modal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form action="{{route('deliverable.store.tracking.del.dept')}}" method="post"
                                  id="add-performance-form">
                                @csrf
                                <input type="hidden" id="kpi_id" name="kpi_id">
                                <input type="hidden" id="track_id" name="id">
                                <input type="hidden" id="quarter" name="quarter">
                                <input type="hidden" id="year" name="year">
                                <!-- BEGIN: Modal Header -->
                                <div class="modal-header">
                                    <h2 class="font-medium text-base mr-auto">
                                        Add Performance Tracking to <span id="kpi"></span>
                                    </h2>

                                </div> <!-- END: Modal Header -->
                                <!-- BEGIN: Modal Body -->
                                <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                                    <div class="col-span-6 sm:col-span-6">
                                        <label for="tracking_date" class="form-label">Tracking Date <span
                                                class="text-red-500">*</span></label>
                                        <input id="tracking_date" type="date" class="form-control"
                                               name="tracking_date" required>
                                    </div>
                                    <div class="col-span-6 sm:col-span-6">
                                        <label for="milestone" class="form-label">Milestone <span
                                                class="text-red-500">*</span></label>
                                        <input id="milestone" type="number" class="form-control"
                                               name="milestone" step="any" min="0" required>
                                    </div>
                                    <div class="col-span-6 sm:col-span-6">
                                        <label for="actual_value" class="form-label">Actual Value <span
                                                class="text-red-500">*</span></label>
                                        <input id="actual_value" type="number" class="form-control"
                                               name="actual_value" step="any" min="0" required>
                                    </div>
                                    <div class="col-span-6 sm:col-span-6">
                                        <label for="quarter_display" class="form-label">Quarter</label>
                                        <input id="quarter_display" type="text" class="form-control" readonly>
                                    </div>
                                    <div class="col-span-6 sm:col-span-6">
                                        <label for="year_display" class="form-label">Year</label>
                                        <input id="year_display" type="text" class="form-control" readonly>
                                    </div>
                                    <div class="col-span-12 sm:col-span-12">
                                        <label for="remarks" class="form-label">Remarks</label>
                                        <textarea name="remarks" id="remarks" class="form-control" rows="3"></textarea>
                                    </div>

                                </div> <!-- END: Modal Body -->
                                <!-- BEGIN: Modal Footer -->
                                <div class="modal-footer">
                                    <button type="button" data-tw-dismiss="modal"
                                            class="btn btn-outline-secondary w-20 mr-1">Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary w-20">Save</button>
                                </div> <!-- END: Modal Footer -->
                            </form>
                        </div>
                    </div>
                </div> <!-- END: Modal Content -->

                <div id="view-performance" class="modal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2 class="font-medium text-base mr-auto">
                                    Performance Tracking for <span id="kpi_title"></span> (<span id="quarter"></span>)
                                </h2>
                            </div>
                            <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                                <div class="col-span-12 sm:col-span-12" id="track-details"></div>
                                <div class="col-span-12 sm:col-span-12 mt-3" id="attachments"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" data-tw-dismiss="modal"
                                        class="btn btn-outline-secondary w-20 mr-1">Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Delivery Department Value Modal -->
                <div id="add-delivery-dept-modal" class="modal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form action="{{route('performance.tracking.facilitator.confirm')}}" method="post"
                                  id="facilitator-confirm-form">
                                @csrf
                                <input type="hidden" id="facilitator_track_id" name="track_id">
                                <!-- BEGIN: Modal Header -->
                                <div class="modal-header">
                                    <h2 class="font-medium text-base mr-auto">
                                        Add Delivery Department Value - <span id="facilitator_kpi_name"></span>
                                    </h2>
                                </div> <!-- END: Modal Header -->
                                <!-- BEGIN: Modal Body -->
                                <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                                    <!-- MDA/Sector Submission Details -->
                                    <div class="col-span-12">
                                        <h3 class="font-semibold text-lg mb-3 border-b pb-2">MDA/Sector Submission</h3>
                                        <div class="grid grid-cols-12 gap-4 gap-y-3">
                                            <div class="col-span-6 sm:col-span-6">
                                                <label class="form-label font-medium">Quarter</label>
                                                <div class="form-control bg-slate-50" id="facilitator_quarter_display"
                                                     readonly>Q1
                                                </div>
                                            </div>
                                            <div class="col-span-6 sm:col-span-6">
                                                <label class="form-label font-medium">Year</label>
                                                <div class="form-control bg-slate-50" id="facilitator_year_display"
                                                     readonly>2024
                                                </div>
                                            </div>
                                            <div class="col-span-6 sm:col-span-6">
                                                <label class="form-label font-medium">Tracking Date</label>
                                                <div class="form-control bg-slate-50"
                                                     id="facilitator_tracking_date_display" readonly>-
                                                </div>
                                            </div>
                                            <div class="col-span-6 sm:col-span-6">
                                                <label class="form-label font-medium">Milestone</label>
                                                <div class="form-control bg-slate-50" id="facilitator_milestone_display"
                                                     readonly>-
                                                </div>
                                            </div>
                                            <div class="col-span-6 sm:col-span-6">
                                                <label class="form-label font-medium">Actual Value</label>
                                                <div class="form-control bg-slate-50 font-semibold"
                                                     id="facilitator_actual_value_display" readonly>-
                                                </div>
                                            </div>
                                            <div class="col-span-12 sm:col-span-12">
                                                <label class="form-label font-medium">Remarks</label>
                                                <div class="form-control bg-slate-50 min-h-[60px]"
                                                     id="facilitator_remarks_display" readonly>-
                                                </div>
                                            </div>
                                            <div class="col-span-12 sm:col-span-12"
                                                 id="facilitator_attachments_display">
                                                <label class="form-label font-medium">Attachments</label>
                                                <div class="form-control bg-slate-50 min-h-[60px]"
                                                     id="facilitator_attachments_list">
                                                    Loading attachments...
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delivery Department Form -->
                                    <div class="col-span-12 mt-4">
                                        <h3 class="font-semibold text-lg mb-3 border-b pb-2">Facilitator Review</h3>
                                        <div class="grid grid-cols-12 gap-4 gap-y-3">
                                            <div class="col-span-12 sm:col-span-12">
                                                <label for="facilitator_decision" class="form-label">Decision <span class="text-red-500">*</span></label>
                                                <select id="facilitator_decision" name="facilitator_decision" class="form-control" required>
                                                    <option value="">Select Decision</option>
                                                    <option value="Accept">Accept</option>
                                                    <option value="Reject">Reject</option>
                                                </select>
                                            </div>
                                            <div class="col-span-6 sm:col-span-6" id="delivery_value_container">
                                                <label for="facilitator_delivery_department_value" class="form-label">Delivery
                                                    Department Value <span class="text-red-500">*</span></label>
                                                <input id="facilitator_delivery_department_value" type="number"
                                                       class="form-control"
                                                       name="delivery_department_value" step="any" min="0">
                                            </div>
                                            <div class="col-span-12 sm:col-span-12" id="rejection_reason_container" style="display: none;">
                                                <label for="facilitator_rejection_reason" class="form-label">Rejection Reason <span class="text-red-500">*</span></label>
                                                <textarea name="facilitator_rejection_reason"
                                                          id="facilitator_rejection_reason"
                                                          class="form-control" rows="3"></textarea>
                                            </div>
                                            <div class="col-span-12 sm:col-span-12" id="remark_container" style="display: none;">
                                                <label for="facilitator_delivery_department_remark" class="form-label">Remarks <span class="text-red-500">*</span></label>
                                                <textarea name="delivery_department_remark"
                                                          id="facilitator_delivery_department_remark"
                                                          class="form-control" rows="3"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div> <!-- END: Modal Body -->
                                <!-- BEGIN: Modal Footer -->
                                <div class="modal-footer">
                                    <button type="button" data-tw-dismiss="modal"
                                            class="btn btn-outline-secondary w-20 mr-1">Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary w-20">Submit</button>
                                </div> <!-- END: Modal Footer -->
                            </form>
                        </div>
                    </div>
                </div> <!-- END: Modal Content -->
            </div>
        </div>

        {{--        <div class="col-span-12 lg:col-span-6 2xl:col-span-6">--}}
        {{--            <div class="box p-5 rounded-md">--}}
        {{--                --}}{{--TODO: Add First Chart Here--}}
        {{--            </div>--}}
        {{--        </div>--}}

        {{--        <div class="col-span-12 lg:col-span-6 2xl:col-span-6">--}}
        {{--            <div class="box p-5 rounded-md">--}}
        {{--                --}}{{--TODO: Add Second Chart Here--}}
        {{--            </div>--}}
        {{--        </div>--}}

        {{--        <div class="col-span-12 lg:col-span-6 2xl:col-span-6">--}}
        {{--            <div class="box p-5 rounded-md">--}}
        {{--                --}}{{--TODO: Add Third Chart Here--}}
        {{--            </div>--}}
        {{--        </div>--}}
    </div>

@endsection
@section('js')
    <script src="{{asset('dist/js/jquery.min.js')}}"></script>
    <script>
        $(function () {
            $('body .add').on('click', function () {
                var trackId = $(this).data('track-id');
                var kpiId = $(this).data('kpi-id');
                var kpiName = $(this).data('kpi');
                var quarter = $(this).data('quarter');
                var year = $(this).data('year');
                var trackingDate = $(this).data('tracking-date');
                var milestone = $(this).data('milestone');
                var actualValue = $(this).data('actual-value');
                var remarks = $(this).data('remarks');

                // Set basic fields
                $('#kpi').html(kpiName);
                $('#track_id').val(trackId);
                $('#kpi_id').val(kpiId);
                $('#quarter').val(quarter);
                $('#year').val(year);

                // Display quarter and year (readonly)
                $('#quarter_display').val('Q' + quarter);
                $('#year_display').val(year);

                // Populate form fields with existing tracking data
                if (trackingDate) {
                    $('#tracking_date').val(trackingDate);
                } else {
                    $('#tracking_date').val('');
                }

                if (milestone) {
                    $('#milestone').val(milestone);
                } else {
                    $('#milestone').val('');
                }

                if (actualValue) {
                    $('#actual_value').val(actualValue);
                } else {
                    $('#actual_value').val('');
                }

                if (remarks) {
                    $('#remarks').val(remarks);
                } else {
                    $('#remarks').val('');
                }
            });

            $('.view').on('click', function () {
                $('#quarter').html($(this).data('qt'))
                $('#kpi_title').html($(this).data('kpi'))
                let id = $(this).data('id')
                let kpi = $(this).data('kpi-id')

                $.get('{{ route('performance.tracking', [':kpi',':id']) }}'.replace(':id', id).replace(':kpi', kpi),
                    function (response) {
                        $('#track-details').html(response)
                    }
                )
            });

            // Handle Facilitator view click (clicking on actual_value)
            $('.view-facilitator').on('click', function () {
                $('#quarter').html($(this).data('qt'))
                $('#kpi_title').html($(this).data('kpi'))
                let id = $(this).data('id')
                let kpi = $(this).data('kpi-id')

                $.get('{{ route('performance.tracking', [':kpi',':id']) }}'.replace(':id', id).replace(':kpi', kpi),
                    function (response) {
                        $('#track-details').html(response)
                    }
                )

                // Load attachments
                $.get('{{ route('deliverable.kpi.tracking.files',[':id']) }}'.replace(':id', id), function (data) {
                    $('#attachments').html(data)
                }).fail(function () {
                    $('#attachments').html('<p class="text-sm text-slate-500">No attachments found.</p>')
                })
            });

            // Handle Add Delivery Department Value button click
            $(document).on('click', '.add-delivery-dept', function () {
                var trackId = $(this).data('track-id');
                var kpiId = $(this).data('kpi-id');
                var kpiName = $(this).data('kpi');
                var quarter = $(this).data('quarter');
                var year = $(this).data('year');
                var trackingDate = $(this).data('tracking-date');
                var milestone = $(this).data('milestone');
                var actualValue = $(this).data('actual-value');
                var remarks = $(this).data('remarks');

                // Set form fields
                $('#facilitator_track_id').val(trackId);
                $('#facilitator_kpi_name').html(kpiName);
                $('#facilitator_quarter_display').html('Q' + quarter);
                $('#facilitator_year_display').html(year);
                $('#facilitator_tracking_date_display').html(trackingDate || '-');
                $('#facilitator_milestone_display').html(milestone || '-');
                $('#facilitator_actual_value_display').html(actualValue || '-');
                $('#facilitator_remarks_display').html(remarks || '-');

                // Clear form fields
                $('#facilitator_decision').val('');
                $('#facilitator_delivery_department_value').val('');
                $('#facilitator_delivery_department_remark').val('');
                $('#facilitator_rejection_reason').val('');
                
                // Reset visibility
                $('#delivery_value_container').hide();
                $('#rejection_reason_container').hide();
                $('#remark_container').hide();
                $('#facilitator_delivery_department_value').removeAttr('required');
                $('#facilitator_rejection_reason').removeAttr('required');
                $('#facilitator_delivery_department_remark').removeAttr('required');

                // Load attachments
                $('#facilitator_attachments_list').html('Loading attachments...');
                $.get('{{ route('deliverable.kpi.tracking.files',[':id']) }}'.replace(':id', trackId), function (data) {
                    $('#facilitator_attachments_list').html(data)
                }).fail(function () {
                    $('#facilitator_attachments_list').html('<p class="text-sm text-slate-500">No attachments found.</p>')
                })
            });

            // Handle facilitator decision change
            $(document).on('change', '#facilitator_decision', function () {
                var decision = $(this).val();
                if (decision === 'Accept') {
                    $('#delivery_value_container').show();
                    $('#rejection_reason_container').hide();
                    $('#remark_container').show();
                    $('#facilitator_delivery_department_value').attr('required', 'required');
                    $('#facilitator_delivery_department_remark').attr('required', 'required');
                    $('#facilitator_rejection_reason').removeAttr('required').val('');
                } else if (decision === 'Reject') {
                    $('#delivery_value_container').hide();
                    $('#rejection_reason_container').show();
                    $('#remark_container').hide();
                    $('#facilitator_delivery_department_value').removeAttr('required').val('');
                    $('#facilitator_delivery_department_remark').removeAttr('required').val('');
                    $('#facilitator_rejection_reason').attr('required', 'required');
                } else {
                    $('#delivery_value_container').hide();
                    $('#rejection_reason_container').hide();
                    $('#remark_container').hide();
                    $('#facilitator_delivery_department_value').removeAttr('required');
                    $('#facilitator_rejection_reason').removeAttr('required');
                    $('#facilitator_delivery_department_remark').removeAttr('required');
                }
            });

            // Handle facilitator confirmation form submission
            $('#facilitator-confirm-form').on('submit', function (e) {
                e.preventDefault();

                var form = $(this);
                var submitButton = form.find('button[type="submit"]');
                var originalText = submitButton.html();
                var decision = $('#facilitator_decision').val();

                // Validate decision-specific fields
                if (!decision) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Decision Required',
                            text: 'Please select a decision (Accept or Reject).',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert('Please select a decision (Accept or Reject).');
                    }
                    return;
                }

                if (decision === 'Accept') {
                    if (!$('#facilitator_delivery_department_value').val()) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Value Required',
                                text: 'Please enter Delivery Department Value.',
                                confirmButtonText: 'OK'
                            }).then(function() {
                                $('#facilitator_delivery_department_value').focus();
                            });
                        } else {
                            alert('Please enter Delivery Department Value.');
                            $('#facilitator_delivery_department_value').focus();
                        }
                        return;
                    }
                    if (!$('#facilitator_delivery_department_remark').val().trim()) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Remarks Required',
                                text: 'Please provide remarks.',
                                confirmButtonText: 'OK'
                            }).then(function() {
                                $('#facilitator_delivery_department_remark').focus();
                            });
                        } else {
                            alert('Please provide remarks.');
                            $('#facilitator_delivery_department_remark').focus();
                        }
                        return;
                    }
                }

                if (decision === 'Reject' && !$('#facilitator_rejection_reason').val().trim()) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Rejection Reason Required',
                            text: 'Please provide a rejection reason.',
                            confirmButtonText: 'OK'
                        }).then(function() {
                            $('#facilitator_rejection_reason').focus();
                        });
                    } else {
                        alert('Please provide a rejection reason.');
                        $('#facilitator_rejection_reason').focus();
                    }
                    return;
                }

                // Disable submit button
                submitButton.prop('disabled', true).html('Submitting...');

                // Clear previous error messages
                $('.error-message').remove();
                $('.form-control').removeClass('border-red-500');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function (response) {
                        if (response.success) {
                            // Show success message and reload
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: response.message || 'Delivery department value submitted successfully.',
                                    timer: 2000,
                                    timerProgressBar: true
                                }).then(function () {
                                    location.reload();
                                });
                            } else {
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: response.message || 'Delivery department value submitted successfully.',
                                        confirmButtonText: 'OK'
                                    }).then(function() {
                                        location.reload();
                                    });
                                } else {
                                    alert(response.message || 'Delivery department value submitted successfully.');
                                    location.reload();
                                }
                            }
                        } else {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message || 'An error occurred.',
                                    confirmButtonText: 'OK'
                                }).then(function() {
                                    submitButton.prop('disabled', false).html(originalText);
                                });
                            } else {
                                alert(response.message || 'An error occurred.');
                                submitButton.prop('disabled', false).html(originalText);
                            }
                        }
                    },
                    error: function (xhr) {
                        // Re-enable submit button
                        submitButton.prop('disabled', false).html(originalText);

                        if (xhr.status === 422) {
                            // Validation errors
                            var errors = xhr.responseJSON?.errors || {};
                            $.each(errors, function (field, messages) {
                                var input = form.find('[name="' + field + '"]');
                                if (input.length) {
                                    input.addClass('border-red-500');
                                    input.after('<div class="error-message text-red-500 text-xs mt-1">' + messages[0] + '</div>');
                                }
                            });
                        } else {
                            var errorMsg = xhr.responseJSON?.message || xhr.responseText || 'An error occurred. Please try again.';
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: errorMsg,
                                    confirmButtonText: 'OK'
                                });
                            } else {
                                alert(errorMsg);
                            }
                        }
                    }
                });
            });

            // Handle form submission with error handling
            $('#add-performance-form').on('submit', function (e) {
                e.preventDefault();

                var form = $(this);
                var submitButton = form.find('button[type="submit"]');
                var originalText = submitButton.html();

                // Disable submit button
                submitButton.prop('disabled', true).html('Saving...');

                // Clear previous error messages
                $('.error-message').remove();
                $('.form-control').removeClass('border-red-500');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function (response) {
                        // Show success message
                        if (response.success) {
                            // Reload page to show updated data and flash messages
                            location.reload();
                        } else if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            location.reload();
                        }
                    },
                    error: function (xhr) {
                        // Re-enable submit button
                        submitButton.prop('disabled', false).html(originalText);

                        if (xhr.status === 422) {
                            // Validation errors
                            var errors = xhr.responseJSON?.errors || {};
                            $.each(errors, function (field, messages) {
                                var input = form.find('[name="' + field + '"]');
                                if (input.length) {
                                    input.addClass('border-red-500');
                                    input.after('<div class="error-message text-red-500 text-xs mt-1">' + messages[0] + '</div>');
                                }
                            });
                        } else if (xhr.status === 200) {
                            // Sometimes Laravel returns 200 with redirect in response
                            // Reload page to show flash messages
                            location.reload();
                        } else {
                            // Other errors - show alert and reload
                            var errorMsg = xhr.responseJSON?.message || xhr.responseText || 'An error occurred. Please try again.';
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: errorMsg,
                                    confirmButtonText: 'OK',
                                    timer: 2000,
                                    timerProgressBar: true
                                }).then(function() {
                                    location.reload();
                                });
                            } else {
                                alert(errorMsg);
                                // Still reload to show any server-side messages
                                setTimeout(function () {
                                    location.reload();
                                }, 1000);
                            }
                        }
                    }
                });
            });
        })
    </script>
@endsection
