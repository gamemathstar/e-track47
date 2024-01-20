@extends('layouts.app')

@section('content')
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Sector : {{$sector->sector_name}}
        </h2>
        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <button class="btn btn-primary shadow-md mr-2" data-tw-toggle="modal"
                    data-tw-target="#sectorHeadModal">Sector Head
            </button>
            <div class="dropdown ml-auto sm:ml-0">
                <button class="dropdown-toggle btn px-2 box" aria-expanded="false" data-tw-toggle="dropdown">
                    <span class="w-5 h-5 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="24" height="24"
                             viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2"
                             stroke-linecap="round"
                             stroke-linejoin="round"
                             icon-name="plus"
                             class="lucide lucide-plus w-4 h-4"
                             data-lucide="plus">
                            <line x1="12" y1="5" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </span>
                </button>
                <div class="dropdown-menu w-40">
                    <ul class="dropdown-content">
                        <li>
                            <a href="" class="dropdown-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round" icon-name="file" data-lucide="file"
                                     class="lucide lucide-file w-4 h-4 mr-2">
                                    <path d="M14.5 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V7.5L14.5 2z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                                Export Word
                            </a>
                        </li>
                        <li>
                            <a href="" class="dropdown-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round" icon-name="file" data-lucide="file"
                                     class="lucide lucide-file w-4 h-4 mr-2">
                                    <path d="M14.5 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V7.5L14.5 2z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                                Export PDF </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="intro-y grid grid-cols-11 gap-5 mt-5">
        <div class="col-span-12 lg:col-span-4 2xl:col-span-3">
            <div class="box p-5 rounded-md">
                <div class="flex items-center border-b border-slate-200/60 dark:border-darkmode-400 pb-5 mb-5">
                    <div class="font-medium text-base truncate">Commitments</div>
                    <a href="javascript:;" class="flex items-center ml-auto text-primary" data-tw-toggle="modal"
                       data-tw-target="#header-footer-modal-preview">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             icon-name="edit" data-lucide="edit" class="lucide lucide-edit w-4 h-4 mr-2">
                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Add New
                    </a>

                    <div id="header-footer-modal-preview" class="modal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{route('commitments.save')}}" method="post">
                                    @csrf
                                    <input type="hidden" name="sector_id" value="{{$sector->id}}">
                                    <!-- BEGIN: Modal Header -->
                                    <div class="modal-header">
                                        <h2 class="font-medium text-base mr-auto">Add Commitment
                                            to {{$sector->sector_name}}</h2>

                                    </div> <!-- END: Modal Header -->
                                    <!-- BEGIN: Modal Body -->
                                    <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                                        <div class="col-span-12 sm:col-span-12">
                                            <label for="modal-form-1" class="form-label">Commitment</label>
                                            <input id="modal-form-1" type="text" class="form-control"
                                                   name="commitment_title" required>
                                        </div>
                                        <div class="col-span-12 sm:col-span-12">
                                            <label for="modal-form-2" class="form-label">Description</label>
                                            <textarea name="description" id="" class="form-control" required></textarea>
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

                    <div id="addDeliverablesModal" class="modal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{route('deliverable.save')}}" method="post">
                                    @csrf
                                    <input type="hidden" name="commitment_id" id="addDeliverableBtnComId" value="">
                                    <!-- BEGIN: Modal Header -->
                                    <div class="modal-header">
                                        <h2 class="font-medium text-base mr-auto">
                                            Add Deliverable
                                        </h2>

                                    </div> <!-- END: Modal Header -->
                                    <!-- BEGIN: Modal Body -->
                                    <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                                        <div class="col-span-12 sm:col-span-12">
                                            <label for="modal-form-1" class="form-label">Deliverable</label>
                                            <input id="deliverable_title" type="text" class="form-control"
                                                   name="deliverable_title" required>
                                        </div>
                                        <div class="col-span-12 sm:col-span-12">
                                            <label for="modal-form-2" class="form-label">Description</label>
                                            <textarea name="description" id="del_deliverable_title" class="form-control"
                                                      required></textarea>
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

                    <div id="editCommitmentModal" class="modal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{route('commitments.update')}}" method="post">
                                    @csrf
                                    <input type="hidden" name="commitment_id" id="comm-id" value="">
                                    <!-- BEGIN: Modal Header -->
                                    <div class="modal-header">
                                        <h2 class="font-medium text-base mr-auto">Edit Commitment
                                        </h2>

                                    </div> <!-- END: Modal Header -->
                                    <!-- BEGIN: Modal Body -->
                                    <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                                        <div class="col-span-12 sm:col-span-12">
                                            <label for="modal-form-1" class="form-label">Commitment</label>
                                            <input type="text" class="form-control"
                                                   name="commitment_title" id="comm-title" required>
                                        </div>
                                        <div class="col-span-12 sm:col-span-12">
                                            <label for="modal-form-2" class="form-label">Description</label>
                                            <textarea name="description" id="comm-desc" class="form-control"
                                                      required></textarea>
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

                    <div id="viewDeliverablesModal" class="modal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">

                                <div class="modal-body" id="viewDeliverableLoadAre">

                                </div>

                                <div class="modal-footer">
                                    <button type="button" data-tw-dismiss="modal"
                                            class="btn btn-outline-secondary w-20 mr-1">Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div> <!-- END: Modal Content -->

                    <div id="sectorHeadModal" class="modal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">

                                <div class="modal-body">

                                    <h1>Sector Heads</h1>
                                    <div class="overflow-x-auto">
                                        <table class="table table-striped">
                                            <thead>
                                            <tr>
                                                <th class="whitespace-nowrap">#</th>
                                                <th class="whitespace-nowrap">Full Name</th>
                                                <th class="whitespace-nowrap">Email Address</th>
                                                <th class="whitespace-nowrap">Phone Number</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td>1</td>
                                                <td>Angelina</td>
                                                <td>Jolie</td>
                                                <td>@angelinajolie</td>
                                            </tr>
                                            <tr>
                                                <td>2</td>
                                                <td>Brad</td>
                                                <td>Pitt</td>
                                                <td>@bradpitt</td>
                                            </tr>
                                            <tr>
                                                <td>3</td>
                                                <td>Charlie</td>
                                                <td>Hunnam</td>
                                                <td>@charliehunnam</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                </div>

                                <div class="modal-footer">
                                    <button type="button" data-tw-dismiss="modal"
                                            class="btn btn-outline-secondary w-20 mr-1">Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div> <!-- END: Modal Content -->

                </div>

                @if($commitments->count())
                    @foreach($commitments as $commitment)
                        <div class="flex items-center">

                            {{$loop->iteration}}.
                            <a href="javascript:;" class="underline ml-1 commitments" com-id="{{$commitment->id}}">
                                {{$commitment->title(48)}}
                            </a>
                        </div>
                        <hr>
                    @endforeach
                @else
                    <center>
                        Click <em class="text-success">Add New</em> to add commitments.
                    </center>
                @endif
            </div>
        </div>
        <div class="col-span-12 lg:col-span-7 2xl:col-span-8">
            <div class="box p-5 rounded-md" id="loadArea">

            </div>
        </div>
    </div>

    <div id="next-overlapping-modal-preview" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <h2>Add KPI for <strong class="font-bold" id="del-title"></strong></h2>
                    <input type="hidden" name="deliverable_id" value="" id="deliverable_id">
                    <hr>
                    <div class="text-warning mt-2" id="addKpiMsg">

                    </div>
                    <div class="mt-3">
                        <label for="regular-form-2" class="form-label">KPI</label>
                        <select name="" class="form-control form-control-rounded" id="kpi_id">
                            <option value="">Select KPI</option>
                            @foreach(\App\Models\Kpi::get() as $kpi)
                                <option value="{{$kpi->id}}">{{$kpi->kpi}} - {{$kpi->unit_of_measurement}}</option>
                            @endforeach
                        </select>

                    </div>
                    <div class="mt-3">
                        <label for="regular-form-2" class="form-label">Year</label>
                        <input id="year" type="number" class="form-control form-control-rounded" placeholder="Year"
                               name="year">
                    </div>
                    <div class="mt-3">
                        <label for="regular-form-2" class="form-label">Target</label>
                        <input id="target" type="text" class="form-control form-control-rounded" placeholder="Target"
                               name="target">
                    </div>
                    <div class="mt-3">
                        <label for="regular-form-2" class="form-label">Actual Value</label>
                        <input id="actual_value" type="text" class="form-control form-control-rounded"
                               placeholder="Actual Value" name="actual_value">
                    </div>
                    <hr>
                    <div class="mt-3 text-center">
                        <button class="btn btn-primary btn-sm btn-rounded" id="addEditDeliverableBtn">Save KPI</button>
                    </div>


                </div>
            </div>
        </div>
    </div>

@endsection
@section('js')
    <script src="{{asset('dist/js/jquery.min.js')}}"></script>

    <script>
        $(function () {
            url = "{{route('sectors.view',['id'=>$sector->id])}}/";
            const editCommitmentModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#editCommitmentModal"));
            const addDeliverablesModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#addDeliverablesModal"));
            const viewDeliverablesModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#viewDeliverablesModal"));
            const addKPIModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#next-overlapping-modal-preview"));

            // $("#year").on('change', function (e) {
            //     document.location = url + $(this).val();
            // });

            @if($comm_id)
            loadCommitments({{$comm_id}});
            @endif


            $(".commitments").on('click', function (e) {
                // $("#loadArea").load("commitments.deliverables");
                loadCommitments($(this).attr('com-id'));
            });

            $('body').on('click', '#editCommitmentBtn', function () {
                com_id = $(this).attr('com-id');
                com_title = $(this).attr('com-title');
                com_description = $(this).attr('com-description');
                $("#comm-title").val(com_title);
                $("#comm-desc").text(com_description);
                $("#comm-id").val(com_id);
                editCommitmentModal.show();
            });

            $('body').on('click', '#addDeliverableBtn', function () {
                com_id = $(this).attr('com-id');
                $("#addDeliverableBtnComId").val(com_id);
                addDeliverablesModal.show();
            });

            $('body').on('click', '.viewDeliverable', function () {
                // viewDeliverableLoadAre
                $("#viewDeliverableLoadAre").load("{{route('deliverable.view')}}?id=" + $(this).attr('del-id'));
                viewDeliverablesModal.show();
            });

            $('body').on('click', '#addEditDeliverableBtn', function () {

                $('#deliverable_id').val($('#addKpiModalBtn').attr('del-id'));
                year = $('#year').val();
                actual_value = $('#actual_value').val();
                target = $('#target').val();
                measurement_unit = $('#measurement_unit').val();
                kpi_id = $('#kpi_id').val();
                deliverable_id = $('body').find('#addKpiModalBtn').attr('del-id');
                $.ajax({
                    type: 'get',
                    url: '{{route('deliverable.add.kpi')}}',
                    data: {
                        deliverable_id: deliverable_id,
                        kpi_id: kpi_id,
                        target: target,
                        actual_value: actual_value,
                        year: year
                    },
                    success: function (data) {
                        console.log(data);
                        if (data.status == 1) {
                            addKPIModal.hide();
                            $("#viewDeliverableLoadAre").load("{{route('deliverable.view')}}?id=" + deliverable_id);
                        } else {
                            $("#addKpiMsg").html(data.message);
                        }
                    }
                });
            });


        });

        function loadCommitments(id) {
            $.ajax({
                type: 'Post',
                url: "{{route("commitments.deliverables",[''])}}/" + id,
                data: {_token: '{{ csrf_token() }}'},
                success: function (data) {
                    $("#loadArea").html(data);
                }
            });
        }
    </script>

@endsection
