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
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-body">
                            <form action="{{route('users.add')}}" method="post">
                                <h2>Add User</h2>
                                <hr> @csrf
                                <div class="grid grid-cols-12 mt-4">
                                    <div class="col-span-12 lg:col-span-4">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" id="full_name" class="form-control" name="full_name"
                                               placeholder="Full Name" required>
                                    </div>
                                    <div class="col-span-12 lg:col-span-4 mr-2  ml-2">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" id="email" class="form-control" name="email"
                                               placeholder="Email" required>
                                    </div>

                                    <div class="col-span-12 lg:col-span-4">
                                        <label for="phone_number" class="form-label">Phone No</label>
                                        <input type="tel" id="phone_number" class="form-control" name="phone_number"
                                               placeholder="Phone No" required>
                                    </div>
                                </div>

                                <div class="grid grid-cols-12 mt-4">
                                    <div class="col-span-12 lg:col-span-4 mr-">
                                        <label for="regular-form-2" class="form-label">User Type</label>
                                        <select name="role" id="role" class="form-control">
                                            <option value="">Select</option>
                                            <option value="Governor"> Governor</option>
                                            <option value="System Admin"> System Admin</option>
                                            <option value="Sector Head"> Sector Head</option>
                                            <option value="Sector Admin">Sector Admin</option>
                                            <option value="Delivery Department">Delivery Department</option>
                                        </select>
                                    </div>

                                    <div class="col-span-12 lg:col-span-4 ml-1  hidden" id="sectorArea">
                                        <label for="regular-form-2" class="form-label">Sector</label>
                                        <select name="sector_id" id="sector" class="form-control">
                                            <option value="">Select</option>
                                            @foreach($sectors as $sektor)
                                                <option value="{{$sektor->id}}">{{$sektor->sector_name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <br>
                                <br>
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
                $sector = $user->sector();
            @endphp

            <div class="intro-y col-span-12 md:col-span-6">
                <div class="box">
                    <div class="flex flex-col lg:flex-row items-center p-5">
                        <div class="w-24 h-24 lg:w-12 lg:h-12 image-fit lg:mr-1">
                            <img alt="User Photo" class="rounded-full"
                                 src="{{ asset($user->image_url? 'uploads/users/' . $user->image_url: 'dist/images/profile-5.jpg') }}">
                        </div>
                        <div class="lg:ml-2 lg:mr-auto text-center lg:text-left mt-3 lg:mt-0">
                            <a href="" class="font-medium">{{ $user->full_name }}</a>
                            <div class="text-slate-500 text-xs mt-0.5">
                                {{ $user->role()? $user->role()->role : ''}} {{ $sector? " | " . $sector->sector_name: ""}}
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

            $("#role").on('change', function () {
                console.log($(this).val());
                if ($(this).val() === 'Sector Head' || $(this).val() === 'Sector Admin') {
                    $("#sectorArea").show();
                } else {
                    $("#sectorArea").hide();
                }
            });
        });
    </script>

@endsection
