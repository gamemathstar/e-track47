@extends("layouts.app")


@section('content')
    <h2 class="intro-y text-lg font-medium mt-10">
        System Users
    </h2>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 flex flex-wrap sm:flex-nowrap items-center mt-2">
            <button class="btn btn-primary shadow-md mr-2" data-tw-toggle="modal"
                    data-tw-target="#addUserModal">Add New User
            </button>

            <div id="addUserModal" class="modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-body">
                            <form action="{{route('users.add')}}" method="post">
                                <h2>Add User</h2>
                                <hr> @csrf
                                <div class="text-warning mt-2" id="addKpiMsg">

                                </div>
                                <div class="mt-2">
                                    <label for="regular-form-2" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="full_name" placeholder="Full Name">

                                </div>
                                <div class="mt-2">
                                    <label for="regular-form-2" class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" placeholder="Username">

                                </div>
                                <div class="mt-3">
                                    <label for="regular-form-2" class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" placeholder="Email">

                                </div>
                                <div class="mt-3">
                                    <label for="regular-form-2" class="form-label">Phone No</label>
                                    <input type="text" class="form-control" name="phone_number" placeholder="Phone No">
                                </div>
                                <div class="mt-3">
                                    <label>User Role</label>
                                    <div class="flex flex-col sm:flex-row mt-2">
                                        <div class="form-check mr-2">
                                            <input id="radio-switch-4" class="form-check-input role" type="radio"
                                                   name="role" value="1">
                                            <label class="form-check-label" for="radio-switch-4">Governor</label>
                                        </div>
                                        <div class="form-check mr-2 mt-2 sm:mt-0">
                                            <input id="radio-switch-5" class="form-check-input role" type="radio"
                                                   name="role" value="0">
                                            <label class="form-check-label" for="radio-switch-5">System Admin</label>
                                        </div>
                                        <div class="form-check mr-2 mt-2 sm:mt-0">
                                            <input id="radio-switch-6" class="form-check-input role" type="radio"
                                                   name="role" value="2">
                                            <label class="form-check-label" for="radio-switch-6">Sector Head</label>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div id="sectorArea" class="hidden">

                                    <div class="mt-3">
                                        <label for="regular-form-2" class="form-label">Sector</label>
                                        <select name="sector_id" id="" class="form-control">
                                            <option value="">Select</option>
                                            @foreach($sectors as $sector)
                                                <option value="{{$sector->id}}">{{$sector->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mt-3">
                                        <label for="regular-form-2" class="form-label">Date From</label>
                                        <input type="date" class="form-control" name="date_from">
                                    </div>
                                    <div class="mt-3">
                                        <label for="regular-form-2" class="form-label">Date To</label>
                                        <input type="date" class="form-control" name="date_to">
                                    </div>
                                </div>

                                <br>
                                <br>
                                <hr>
                                <div class="mt-3 text-center">
                                    <button class="btn btn-primary" id="addEditDeliverableBtn">Save</button>
                                    <button type="button" data-tw-dismiss="modal" class="btn btn-secondary" id="">
                                        Close
                                    </button>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- BEGIN: Users Layout -->
        @foreach($users as $user)
            @php
                $sectorHead = $user->sectorHead();
                $sector = $user->sector();
            @endphp

            <div class="intro-y col-span-12 md:col-span-6">
                <div class="box">
                    <div class="flex flex-col lg:flex-row items-center p-5">
                        <div class="w-24 h-24 lg:w-12 lg:h-12 image-fit lg:mr-1">
                            <img alt="Midone - HTML Admin Template" class="rounded-full"
                                 src="{{ asset('dist/images/profile-1.jpg') }}">
                        </div>
                        <div class="lg:ml-2 lg:mr-auto text-center lg:text-left mt-3 lg:mt-0">
                            <a href="" class="font-medium">{{ $user->full_name }}</a>
                            <div class="text-slate-500 text-xs mt-0.5">
                                {{ $user->role() }} {{ $sector? " | " . $sector->name: ""}}
                            </div>
                        </div>
                        <div class="flex mt-4 lg:mt-0">
                            <a class="btn btn-primary py-1 px-2 mr-2" href="{{route('users.view',[$user->id])}}">
                                View User
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
        <!-- END: Pagination -->
    </div>

@endsection


@section('js')
    <script src="{{asset('dist/js/jquery.min.js')}}"></script>
    <script>
        $(function () {
            const addUserModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#addUserModal"));

            $(".role").on('change', function () {
                console.log($(this).val());
                if ($(this).val() == '2') {
                    $("#sectorArea").show();
                } else {
                    $("#sectorArea").hide();
                }
            });
        });
    </script>

@endsection
