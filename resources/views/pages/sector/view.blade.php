@php use Carbon\Carbon; @endphp
@extends('layouts.app')

@section('content')
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            MDA/Sector : {{$sector->sector_name}}
        </h2>
        <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
            <button class="btn btn-primary shadow-md mr-2" data-tw-toggle="modal"
                    data-tw-target="#sectorHeadModal">MDA/Sector Head
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
    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12 lg:col-span-12 2xl:col-span-12">
            <div class="box p-5 rounded-md">
                <div class="flex items-center border-slate-200/60 dark:border-darkmode-400">
                    <div class="text-primary text-2xl">{{ $sector->sector_name }}</div>
                </div>
                {{ $sector->description }}
                <a class="btn btn-primary w-24 float-right" href="{{ route("sectors.show",$sector->id) }}"
                   target="_blank">Download Report</a>
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
                    Add New Commitment
                </a>
                @if(session('success'))
                    <div class="alert alert-success-soft alert-dismissible show flex items-center mb-2 mt-5"
                         role="alert">
                        <i data-lucide="alert-triangle" class="w-6 h-6 mr-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-tw-dismiss="alert" aria-label="Close">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                @endif
                @if(session('failure'))
                    <div class="alert alert-danger-soft alert-dismissible show flex items-center mb-2 mt-5"
                         role="alert"><i
                            data-lucide="alert-octagon" class="w-6 h-6 mr-2"></i> {{ session('failure') }}
                        <button type="button" class="btn-close" data-tw-dismiss="alert" aria-label="Close">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                @endif
                @if($commitments->count())
                    <table class="table table-report mt-2">
                        <thead>
                        <tr>
                            <th class="whitespace-nowrap">#</th>
                            <th class="whitespace-nowrap">Commitment</th>
                            {{--                            <th class="whitespace-nowrap">Budget</th>--}}
                            <th class="whitespace-nowrap">Start Date</th>
                            <th class="whitespace-nowrap">Duration</th>
                            <th class="text-center whitespace-nowrap">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($commitments as $commitment)
                            <tr>
                                <td>
                                    {{$loop->iteration}}
                                </td>
                                <td>
                                    <a href="javascript:;" class="ml-1">{{$commitment->title(48)}}</a>
                                </td>
                                {{--                                <td>&#8358;{{ number_format($commitment->budget) }}</td>--}}
                                <td>{{ $commitment->start_date?Carbon::parse($commitment->start_date)->format('d M, Y'):'---' }}</td>
                                <td>{{ $commitment->duration_in_days? $commitment->duration_in_days.' day(s)':'---' }}</td>
                                <td>
                                    <div class="flex justify-center items-center">
                                        <a class="flex items-center text-warning mr-3 tooltip edit" data-theme="dark"
                                           title="Edit Commitment" href="javascript:;" data-tw-toggle="modal"
                                           data-tw-target="#edit-photo" data-id="{{$commitment->id}}"
                                           data-photo="{{ secure_asset(( is_null($commitment->img_url)? 'dist/images/preview-3.jpg':'uploads/'.$commitment->img_url)) }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24"
                                                 fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round"
                                                 stroke-linejoin="round" icon-name="check-square"
                                                 data-lucide="check-square"
                                                 class="lucide lucide-check-square w-4 h-4 mr-1">
                                                <polyline points="9 11 12 14 22 4"></polyline>
                                                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
                                            </svg>
                                        </a>
                                        <a class="flex items-center mr-3  items-center text-success tooltip"
                                           data-theme="dark" title="View Commitment"
                                           href="{{route('commitments.deliverables',[$commitment->id])}}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24"
                                                 fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round"
                                                 stroke-linejoin="round" icon-name="eye" data-lucide="eye"
                                                 class="lucide lucide-eye block mx-auto">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </a>
                                        <a class="flex items-center text-danger tooltip" data-theme="dark"
                                           title="Delete Commitment" href="javascript:;" data-tw-toggle="modal"
                                           data-tw-target="#delete-modal-preview{{$commitment->id}}">
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
                                    <div id="delete-modal-preview{{$commitment->id}}" class="modal" tabindex="-1"
                                         aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-body p-0">
                                                    <div class="p-5 text-center"><i data-lucide="x-circle"
                                                                                    class="w-16 h-16 text-danger mx-auto mt-3"></i>
                                                        <div class="text-3xl mt-5">Are you sure?</div>
                                                        <div class="text-slate-500 mt-2">Do you really want to delete
                                                            this
                                                            Commitment? <br>
                                                            <strong>{{$commitment->title(48)}}</strong>
                                                        </div>
                                                    </div>
                                                    <div class="px-5 pb-8 text-center">
                                                        <button type="button" data-tw-dismiss="modal"
                                                                class="btn btn-outline-secondary w-24 mr-1">Cancel
                                                        </button>
                                                        <a href="{{ route('commitments.delete',[$commitment->id]) }}"
                                                           class="btn btn-danger w-24">Delete</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <center>
                        Click <em class="text-success">Add New </em> to add commitments.
                    </center>
                @endif

                <div id="edit-photo" class="modal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form action="{{route('commitments.change.photo')}}" method="post"
                                  enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="commitment_id" id="commitmentId">
                                <!-- BEGIN: Modal Header -->
                                <div class="modal-header">
                                    <h2 class="font-medium text-base mr-auto">Edit Commitment Photo</h2>
                                </div> <!-- END: Modal Header -->
                                <!-- BEGIN: Modal Body -->
                                <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                                    <div class="col-span-12 sm:col-span-12 h-40 2xl:h-56 image-fit">
                                        <img class="rounded-md" id="commitmentPhoto"/>
                                    </div>
                                    <div class="col-span-6 sm:col-span-6">
                                        <label for="modal-form-2" class="form-label">Picture</label>
                                        <input type="file" name="img_url" id="" class="form-control">
                                    </div>
                                </div> <!-- END: Modal Body -->
                                <!-- BEGIN: Modal Footer -->
                                <div class="modal-footer">
                                    <button type="button" data-tw-dismiss="modal"
                                            class="btn btn-outline-secondary w-20 mr-1">Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary w-20">Change</button>
                                </div> <!-- END: Modal Footer -->
                            </form>
                        </div>
                    </div>
                </div>

                <div id="header-footer-modal-preview" class="modal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form action="{{route('commitments.save')}}" method="post" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="sector_id" value="{{$sector->id}}">
                                <!-- BEGIN: Modal Header -->
                                <div class="modal-header">
                                    <h2 class="font-medium text-base mr-auto">Add Commitment
                                        to {{$sector->sector_name}}</h2>

                                </div> <!-- END: Modal Header -->
                                <!-- BEGIN: Modal Body -->
                                <div class="modal-body grid grid-cols-12 gap-4 gap-y-3">
                                    <div class="col-span-6 sm:col-span-6">
                                        <label for="modal-form-1" class="form-label">Commitment Title</label>
                                        <input id="modal-form-1" type="text" class="form-control"
                                               name="name" required>
                                    </div>
                                    <div class="col-span-6 sm:col-span-6">
                                        <label for="modal-form-1" class="form-label">Commitment Type</label>
                                        <input id="modal-form-1" type="text" class="form-control"
                                               name="type" required>
                                    </div>
                                    <div class="col-span-6 sm:col-span-6">
                                        <label for="modal-form-2" class="form-label">Description</label>
                                        <textarea name="description" id="" class="form-control" required></textarea>
                                    </div>
                                    <div class="col-span-6 sm:col-span-6">
                                        <label for="modal-form-2" class="form-label">Picture</label>
                                        <input type="file" name="img_url" id="" class="form-control">
                                    </div>
                                    <div class="col-span-6 sm:col-span-6">
                                        <label for="modal-form-1" class="form-label">Status</label>
                                        <select id="modal-form-1" class="form-control" name="status" required>
                                            <option value="">Select</option>
                                            <option value="Not Started">Not Started</option>
                                            <option value="In Progress">In Progress</option>
                                        </select>
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
                                    {{--                                    <div class="col-span-6 sm:col-span-6">--}}
                                    {{--                                        <label for="modal-form-1" class="form-label">Budget</label>--}}
                                    {{--                                        <input id="modal-form-1" type="text" class="form-control"--}}
                                    {{--                                               name="budget" required>--}}
                                    {{--                                    </div>--}}
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

        <div class="col-span-12 lg:col-span-6 2xl:col-span-6">
            <div class="box p-5 rounded-md">
                <div class="h-[480px]">

                    <canvas id="commitmentStatusChart" width="640" height="640"></canvas>
                </div>
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
    <script src="code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(function () {
            url = "{{route('sectors.view',['id'=>$sector->id])}}/";
            // const editCommitmentModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#editCommitmentModal"));
            // const addDeliverablesModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#addDeliverablesModal"));
            // const viewDeliverablesModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#viewDeliverablesModal"));
            // const addKPIModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#next-overlapping-modal-preview"));

            // $("#year").on('change', function (e) {
            //     document.location = url + $(this).val();
            // });

            $('.edit').on('click', function () {
                //  console.log($(this).data('photo'))
                $('#commitmentId').val($(this).data('id'))
                $('#commitmentPhoto').attr('src', $(this).data('photo'))
            })

            pendingCompleted()

            function pendingCompleted() {

                $.ajax({
                    type: 'get',
                    data: {sector_id: '{{$sector->id}}'},
                    url: "{{route('chart.sector.pending.completed')}}",
                    success: function (data) {
                        // Extracting sector names and commitment counts for the chart
                        const sectorNames = data.map(sector => sector.sector_name);
                        const completedCounts = data.map(sector => sector.completed_commitments_count);
                        const pendingCounts = data.map(sector => sector.pending_commitments_count);

// Creating a side-by-side bar chart
                        const ctx = document.getElementById('commitmentStatusChart').getContext('2d');
                        const myChart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: sectorNames,
                                datasets: [{
                                    label: 'Completed Commitments',
                                    data: completedCounts,
                                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    borderWidth: 1
                                }, {
                                    label: 'Pending Commitments',
                                    data: pendingCounts,
                                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                    borderColor: 'rgba(255, 99, 132, 1)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        max: Math.max(...completedCounts, ...pendingCounts) + 1 // Adjust the max value for better visualization
                                    }
                                }
                            }
                        });

                    }
                });

            }
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
