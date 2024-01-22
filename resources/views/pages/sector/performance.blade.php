@php use Carbon\Carbon; @endphp
@extends('layouts.app')

@section('content')
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium ml-3 mr-auto">KPI</h2>
    </div>
    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12 lg:col-span-12 2xl:col-span-12">
            <div class="box p-5 rounded-md">
                <div class="flex items-center border-slate-200/60 dark:border-darkmode-400 mb-2">
                    <div class="text-primary text-2xl">{{ $kpi->kpi }}</div>
                </div>
                Target: {{ $kpi->target_value }} {{ $kpi->unit_of_measurement }}
                @if($tracking->count())
                    <a class="btn btn-primary w-24 float-right tooltip" data-theme="dark"
                       title="Mark this submission 'Confirm' or 'Rejected'">
                        Finish
                    </a>
                @endif
                <br><br>
            </div>
        </div>
    </div>

    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12 lg:col-span-12 2xl:col-span-12">
            <div class="rounded-md">
                @if(!$tracking->count())
                    <a href="javascript:;" class="btn btn-primary ml-3" data-tw-toggle="modal"
                       data-tw-target="#header-footer-modal-preview">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             icon-name="edit" data-lucide="edit" class="lucide lucide-edit w-4 h-4 mr-2">
                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Add New
                    </a>
                @endif
                @if($tracking->count())
                    <table class="table table-report mt-2">
                        <thead>
                        <tr>
                            <th class="whitespace-nowrap">#</th>
                            <th class="whitespace-nowrap">Tracking Date</th>
                            <th class="whitespace-nowrap">Actual Value</th>
                            <th class="whitespace-nowrap">Remarks</th>
                            <th class="whitespace-nowrap">DP Value</th>
                            <th class="whitespace-nowrap">DP Remark</th>
                            <th class="whitespace-nowrap">Status</th>
                            <th class="text-center whitespace-nowrap">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($tracking as $track)
                            <tr>
                                <td>
                                    {{ $loop->iteration }}
                                </td>
                                <td>
                                    {{ $track->tracking_date?Carbon::parse($track->tracking_date)->format('d M, Y'):'- - -' }}
                                </td>
                                <td>{{ $track->actual_value }} ({{ $kpi->unit_of_measurement }})</td>
                                <td>{{ $track->remarks }}</td>
                                <td>
                                    {{ $track->delivery_department_value?$track->delivery_department_value :'- - -' }}
                                    {{ $track->delivery_department_value? '(' . $kpi->unit_of_measurement . ')' : '' }}
                                </td>
                                <td>{{ $track->delivery_department_remark?$track->delivery_department_remark:'- - -' }}</td>
                                <td>{{ $track->confirmation_status }}</td>
                                <td>
                                    <div class="flex justify-center items-center">
                                        <a class="flex items-center mr-3  items-center text-success tooltip"
                                           data-tw-toggle="modal" data-theme="dark" title="Review this submission"
                                           data-tw-target="#header-footer-modal-preview" href="javascript:;">
                                            <i data-lucide="edit" class="block mx-auto"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <center>
                        Click <em class="text-success">Add New </em> to add performance tracking.
                    </center>
                @endif

                <div id="header-footer-modal-preview" class="modal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            @php $track =  $tracking->first(); @endphp
                            <form action="{{route('deliverable.store.tracking')}}" method="post">
                                @csrf
                                <input type="hidden" name="kpi_id" value="{{ $kpi->id }}">
                                <input type="hidden" name="id" value="{{$tracking->count()? $tracking[0]->id:null }}">
                                <!-- BEGIN: Modal Header -->
                                <div class="modal-header">
                                    <h2 class="font-medium text-base mr-auto">
                                        Add Performance Tracking to {{ $kpi->kpi }}
                                    </h2>

                                </div> <!-- END: Modal Header -->
                                <!-- BEGIN: Modal Body -->
                                <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                                    <div class="col-span-6 sm:col-span-6">
                                        <label for="modal-form-1" class="form-label">Tracking Date</label>
                                        <input id="modal-form-1" type="date" class="form-control"
                                               value="{{$track?Carbon::parse($track->tracking_date)->format('Y-m-d'):''}}"
                                               name="tracking_date" required>
                                    </div>

                                    <div class="col-span-6 sm:col-span-6">
                                        <label for="modal-form-1" class="form-label">Actual Value</label>
                                        <input id="modal-form-1" type="number" class="form-control"
                                               name="actual_value" step="any"
                                               value="{{ $track?$track->actual_value:'' }}"
                                               placeholder="In {{ $kpi->unit_of_measurement }}" required>
                                    </div>

                                    <div class="col-span-12 sm:col-span-12">
                                        <label for="modal-form-1" class="form-label">Remark</label>
                                        <textarea name="remarks" id=""
                                                  class="form-control">{{ $track?$track->remarks:'' }}                                        </textarea>
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
            </div>
        </div>
    </div>

@endsection
@section('js')
    <script src="{{asset('dist/js/jquery.min.js')}}"></script>
    <script>
        $(function () {
        })
    </script>
@endsection
