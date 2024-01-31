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
                &#8358; {{ $deliverable->budget?number_format($deliverable->budget):'Budget Not Set' }}
                <button class="btn btn-primary w-24 float-right">Files</button>
                <br><br>
            </div>
        </div>
    </div>

    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12 lg:col-span-12 2xl:col-span-12">
            <div class="rounded-md">
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
                @if($kpis->count())
                    <table class="table table-bordered table-report mt-2">
                        <thead>
                        <tr>
                            <th class="whitespace-nowrap">#</th>
                            <th class="whitespace-nowrap">KPI</th>
                            <th class="whitespace-nowrap">Target</th>
                            <th class="whitespace-nowrap">Start Date</th>
                            <th class="whitespace-nowrap">1<sup>st</sup> QPT</th>
                            <th class="whitespace-nowrap">2<sup>nd</sup> QPT</th>
                            <th class="whitespace-nowrap">3<sup>rd</sup> QPT</th>
                            <th class="whitespace-nowrap">4<sup>th</sup> QPT</th>
                            <th class="text-center whitespace-nowrap">Action</th>
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
                                <td>{{ Carbon::parse($kpi->start_date)->format('d M, Y') }}</td>
                                <td>
                                    @if(count($tracks)>0)
                                        @php $track = $tracks[0]; @endphp

                                        @if($track->confirmation_status != 'Not Confirmed')
                                        <a href="javascript:;" data-tw-toggle="modal" data-tw-target="#view-performance"
                                           data-id="{{ $track->id }}" data-kpi="{{ $kpi->kpi }}"
                                           data-kpi-id="{{$kpi->id}}" data-qt="1st QT"
                                           class="view text-{{ $track->confirmation_status=='Confirmed'?'success':($track->confirmation_status=='Rejected'?'danger':'') }} block">
                                            {{ $track->actual_value }}
                                        </a>
                                        @else
                                        <a href="javascript:" class="add" data-tw-toggle="modal"
                                           data-id="{{ $track->id }}" data-kpi="{{ $kpi->kpi }}" data-id="{{ $kpi->id }}"
                                           data-tw-target="#add-performance">
                                            {{ $track->actual_value }} <i data-lucide="plus-square" class="block mx-auto"></i>
                                        </a>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if(count($tracks)>1)
                                        @php $track = $tracks[1]; @endphp
                                        @if($track->confirmation_status != 'Not Confirmed')
                                            <a href="javascript:;" data-tw-toggle="modal" data-tw-target="#view-performance"
                                               data-id="{{ $track->id }}" data-kpi="{{ $kpi->kpi }}"
                                               data-kpi-id="{{$kpi->id}}" data-qt="1st QT"
                                               class="view text-{{ $track->confirmation_status=='Confirmed'?'success':($track->confirmation_status=='Rejected'?'danger':'') }} block">
                                                {{ $track->actual_value }}
                                            </a>
                                        @else
                                            <a href="javascript:" class="add" data-tw-toggle="modal"
                                               data-id="{{ $track->id }}"data-kpi="{{ $kpi->kpi }}" data-id="{{ $kpi->id }}"
                                               data-tw-target="#add-performance">
                                                {{ $track->actual_value }} <i data-lucide="plus-square" class="block mx-auto"></i>
                                            </a>
                                        @endif
                                    @endif
                                </td>
                                <td> @if(count($tracks)>2)
                                        @php $track = $tracks[2]; @endphp
                                        @if($track->confirmation_status != 'Not Confirmed')
                                            <a href="javascript:;" data-tw-toggle="modal" data-tw-target="#view-performance"
                                               data-id="{{ $track->id }}" data-kpi="{{ $kpi->kpi }}"
                                               data-kpi-id="{{$kpi->id}}" data-qt="1st QT"
                                               class="view text-{{ $track->confirmation_status=='Confirmed'?'success':($track->confirmation_status=='Rejected'?'danger':'') }} block">
                                                {{ $track->actual_value }}
                                            </a>
                                        @else
                                            <a href="javascript:" class="add" data-tw-toggle="modal"
                                               data-id="{{ $track->id }}"data-kpi="{{ $kpi->kpi }}" data-id="{{ $kpi->id }}"
                                               data-tw-target="#add-performance">
                                                {{ $track->actual_value }} <i data-lucide="plus-square" class="block mx-auto"></i>
                                            </a>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if(count($tracks)>3)
                                        @php $track = $tracks[3]; @endphp
                                        @if($track->confirmation_status != 'Not Confirmed')
                                            <a href="javascript:;" data-tw-toggle="modal" data-tw-target="#view-performance"
                                               data-id="{{ $track->id }}" data-kpi="{{ $kpi->kpi }}"
                                               data-kpi-id="{{$kpi->id}}" data-qt="1st QT"
                                               class="view text-{{ $track->confirmation_status=='Confirmed'?'success':($track->confirmation_status=='Rejected'?'danger':'') }} block">
                                                {{ $track->actual_value }}
                                            </a>
                                        @else
                                            <a href="javascript:" class="add" data-tw-toggle="modal"
                                               data-id="{{ $track->id }}" data-kpi="{{ $kpi->kpi }}" data-id="{{ $kpi->id }}"
                                               data-tw-target="#add-performance">
                                                {{ $track->actual_value }} <i data-lucide="plus-square" class="block mx-auto"></i>
                                            </a>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    <div class="flex justify-center items-center">
                                        <a class="flex items-center text-danger tooltip" data-theme="dark"
                                           title="Delete KPI" href="javascript:;" data-tw-toggle="modal"
                                           data-tw-target="#delete-modal-preview{{ $kpi->id }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24"
                                                 fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round"
                                                 stroke-linejoin="round" icon-name="trash-2" data-lucide="trash-2"
                                                 class="lucide lucide-trash-2 w-4 h-4 mr-1">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path
                                                    d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
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
                                        <label for="modal-form-1" class="form-label">Start Date</label>
                                        <input id="modal-form-1" type="date" class="form-control"
                                               name="start_date" required>
                                    </div>
                                    <div class="col-span-6 sm:col-span-6">
                                        <label for="modal-form-1" class="form-label">End Date</label>
                                        <input id="modal-form-1" type="date" class="form-control"
                                               name="end_date" required>
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
                            <form action="{{route('deliverable.store.tracking.del.dept')}}" method="post">
                                @csrf
                                <input type="hidden" id="kpi_id" name="kpi_id">
                                <input type="hidden" id="track_id" name="id">
                                <!-- BEGIN: Modal Header -->
                                <div class="modal-header">
                                    <h2 class="font-medium text-base mr-auto">
                                        Add Performance Tracking to <span id="kpi"></span>
                                    </h2>

                                </div> <!-- END: Modal Header -->
                                <!-- BEGIN: Modal Body -->
                                <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                                    <div class="col-span-6 sm:col-span-6">
                                        <label for="delivery_department_value" class="form-label">Confirm Value</label>
                                        <input id="delivery_department_value" type="date" class="form-control"
                                               {{--                                               value="{{$track?Carbon::parse($track->tracking_date)->format('Y-m-d'):''}}"--}}
                                               name="delivery_department_value" required>
                                    </div>

                                    <div class="col-span-12 sm:col-span-12">
                                        <label for="delivery_department_remark" class="form-label">Remark</label>
                                        <textarea name="delivery_department_remark" id="delivery_department_remark"
                                                  class="form-control"></textarea>
                                    </div>

                                    <div class="col-span-12 sm:col-span-12">
                                        <label for="delivery_department_remark" class="form-label">Status</label>
                                        <select name="confirmation_status" id="confirmation_status">
                                            <option>Confirmed</option>
                                            <option>Rejected</option>
                                        </select>
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
                            </div>
                            <div class="modal-footer">
                                <button type="button" data-tw-dismiss="modal"
                                        class="btn btn-outline-secondary w-20 mr-1">Close
                                </button>
                                {{--                                <button type="button" class="btn btn-primary w-20">Edit</button>--}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-6 2xl:col-span-6">
            <div class="box p-5 rounded-md">
                {{--TODO: Add First Chart Here--}}
            </div>
        </div>

        <div class="col-span-12 lg:col-span-6 2xl:col-span-6">
            <div class="box p-5 rounded-md">
                {{--TODO: Add Second Chart Here--}}
            </div>
        </div>

        <div class="col-span-12 lg:col-span-6 2xl:col-span-6">
            <div class="box p-5 rounded-md">
                {{--TODO: Add Third Chart Here--}}
            </div>
        </div>
    </div>

@endsection
@section('js')
    <script src="{{asset('dist/js/jquery.min.js')}}"></script>
    <script>
        $(function () {
            $('body .add').on('click', function () {
                $('#kpi').html($(this).data('kpi'))
                $('#track_id').val($(this).data('id'))
            })

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
            })
        })
    </script>
@endsection
