@extends("layouts.app")

@section('content')
    <div class="intro-y flex items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            User Profile
        </h2>
    </div>

    <!-- BEGIN: Profile Info -->
    <div class="intro-y box px-5 pt-5 mt-5">
        <div class="flex flex-col lg:flex-row border-b border-slate-200/60 dark:border-darkmode-400 pb-5 -mx-5">
            <div class="flex flex-1 px-5 items-center justify-center lg:justify-start">
                <div class="w-20 h-20 sm:w-24 sm:h-24 flex-none lg:w-32 lg:h-32 image-fit relative">
                    <img alt="Midone - HTML Admin Template" class="rounded-full"
                         src="{{ asset($user->image_url? 'uploads/users/' . $user->image_url: 'dist/images/profile-5.jpg') }}">
                </div>
                <div class="ml-5">
                    @php $sector = $user->sector(); @endphp
                    <div
                        class="w-24 sm:w-40 truncate sm:whitespace-normal font-medium text-lg">{{ $user->full_name }}</div>
                    <div
                        class="text-slate-500">{{ $user->role()->role }}{{ $sector ? ' | ' . $sector->sector_name : '' }}</div>
                </div>
            </div>
            <div
                class="mt-6 lg:mt-0 flex-1 px-5 border-l border-r border-slate-200/60 dark:border-darkmode-400 border-t lg:border-t-0 pt-5 lg:pt-0">
                <div class="font-medium text-center lg:text-left lg:mt-3">Contact Details</div>
                <div class="flex flex-col justify-center items-center lg:items-start mt-4">
                    <div class="truncate sm:whitespace-normal flex items-center">
                        <i data-lucide="mail" class="w-4 h-4 mr-2"></i>
                        {{ $user->email }}
                    </div>
                    <div class="truncate sm:whitespace-normal flex items-center mt-3">
                        <i data-lucide="phone" class="w-4 h-4 mr-2"></i>
                        {{ $user->phone_number }}
                    </div>
                    @if(!is_null($user->sector()))
                        <div class="truncate sm:whitespace-normal flex items-center mt-3">
                            <i data-lucide="home" class="w-4 h-4 mr-2"></i>
                            {{ $user->sector()->sector_name }}
                        </div>
                    @endif
                </div>
            </div>
            <div
                class="mt-6 lg:mt-0 flex-1 flex items-center justify-center px-5 border-t lg:border-0 border-slate-200/60 dark:border-darkmode-400 pt-5 lg:pt-0">
                {{--                <div class="text-center rounded-md w-20 py-3">--}}
                {{--                    <div class="font-medium text-primary text-xl">201</div>--}}
                {{--                    <div class="text-slate-500">Orders</div>--}}
                {{--                </div>--}}
                {{--                <div class="text-center rounded-md w-20 py-3">--}}
                {{--                    <div class="font-medium text-primary text-xl">1k</div>--}}
                {{--                    <div class="text-slate-500">Purchases</div>--}}
                {{--                </div>--}}
                {{--                <div class="text-center rounded-md w-20 py-3">--}}
                {{--                    <div class="font-medium text-primary text-xl">492</div>--}}
                {{--                    <div class="text-slate-500">Reviews</div>--}}
                {{--                </div>--}}
            </div>
        </div>
        <ul class="nav nav-link-tabs flex-col sm:flex-row justify-center lg:justify-start text-center" role="tablist">
            <li id="profile-tab" class="nav-item" role="presentation">
                <a href="javascript:;" class="nav-link py-4 flex items-center active" data-tw-target="#profile"
                   aria-controls="profile" aria-selected="true" role="tab"> <i class="w-4 h-4 mr-2"
                                                                               data-lucide="user"></i> Profile </a>
            </li>
            <li id="change-photo-tab" class="nav-item" role="presentation">
                <a href="javascript:;" class="nav-link py-4 flex items-center" data-tw-target="#change-photo"
                   aria-selected="false" role="tab"> <i class="w-4 h-4 mr-2" data-lucide="camera"></i> Change Photo </a>
            </li>
            <li id="change-password-tab" class="nav-item" role="presentation">
                <a href="javascript:;" class="nav-link py-4 flex items-center" data-tw-target="#change-password"
                   aria-selected="false" role="tab"> <i class="w-4 h-4 mr-2" data-lucide="lock"></i> Change Password
                </a>
            </li>
            <li id="edit-profile-tab" class="nav-item" role="presentation">
                <a href="javascript:;" class="nav-link py-4 flex items-center" data-tw-target="#edit-profile"
                   aria-selected="false" role="tab"> <i class="w-4 h-4 mr-2" data-lucide="pencil"></i> Edit Profile
                </a>
            </li>
            <li id="settings-tab" class="nav-item" role="presentation">
                <a href="javascript:;" class="nav-link py-4 flex items-center" data-tw-target="#settings"
                   aria-selected="false" role="tab"> <i class="w-4 h-4 mr-2" data-lucide="settings"></i> Settings </a>
            </li>
        </ul>
    </div>
    <!-- END: Profile Info -->

    <div class="tab-content mt-5">
        <div id="profile" class="tab-pane active" role="tabpanel" aria-labelledby="profile-tab">
            <div class="grid grid-cols-12 gap-6">
                <!-- BEGIN: Latest Uploads -->
                <div class="intro-y box col-span-12 lg:col-span-6">
                    {{--                    <div class="p-5">--}}
                    {{--                        <div class="flex items-center">--}}
                    {{--                            <div class="file"> <a href="" class="w-12 file__icon file__icon--directory"></a> </div>--}}
                    {{--                            <div class="ml-4">--}}
                    {{--                                <a class="font-medium" href="">Documentation</a>--}}
                    {{--                                <div class="text-slate-500 text-xs mt-0.5">40 KB</div>--}}
                    {{--                            </div>--}}
                    {{--                        </div>--}}
                    {{--                    </div>--}}
                </div>
                <!-- END: Latest Uploads -->
            </div>
        </div>

        <div id="change-photo" class="tab-pane" role="tabpanel" aria-labelledby="change-photo-tab">
            <div class="grid grid-cols-12 gap-6">
                <!-- BEGIN: Latest Uploads -->
                <div class="intro-y box col-span-12 lg:col-span-6">
                    <div class="p-8">
                        <form method="post" data-single="true" action="{{ route('users.upload.photo') }}"
                              class="p-3"
                              enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <input name="img_url" type="file"/>
                            <button type="submit" class="btn btn-primary mr-3 mb-3 float-right">Upload</button>
                        </form>
                    </div>
                </div>
                <!-- END: Latest Uploads -->
            </div>
        </div>

        <div id="change-password" class="tab-pane" role="tabpanel" aria-labelledby="change-password-tab">
            <div class="grid grid-cols-12 gap-6">
                <!-- BEGIN: Latest Uploads -->
                <div class="intro-y box col-span-12 lg:col-span-6">
                    <div class="p-8">
                        {{--                        <div class="alert alert-danger-soft show flex items-center mb-2" role="alert">--}}
                        {{--                            <i data-lucide="alert-circle" class="w-6 h-6 mr-2"></i>--}}
                        {{--                            <span id="error-msg"></span>--}}
                        {{--                        </div>--}}
                        {{--                        <div class="alert alert-success-soft show flex items-center mb-2" role="alert">--}}
                        {{--                            <i data-lucide="check-circle" class="w-6 h-6 mr-2"></i>--}}
                        {{--                            <span id="success-msg"></span>--}}
                        {{--                        </div>--}}
                        <form method="post" action="{{ route('users.user.change.password') }}">
                            @csrf
                            <input type="hidden" name="id" value="{{ $user->id }}">
                            <div>
                                <label for="new-password" class="form-label">New Password</label>
                                <input id="new-password" name="password" type="password" class="form-control"
                                       placeholder="*****" required>
                            </div>
                            <div class="mt-5">
                                <label for="confirm-password" class="form-label">Confirm Password</label>
                                <input id="confirm-password" type="password" name="confirm_password"
                                       class="form-control" placeholder="******" required>
                            </div>
                            <div class="mt-5">&nbsp;</div>
                            <button type="submit" class="btn btn-primary mt-5">Change</button>
                        </form>
                    </div>
                </div>
                <!-- END: Latest Uploads -->
            </div>
        </div>

        <div id="edit-profile" class="tab-pane" role="tabpanel" aria-labelledby="edit-profile-tab">
            <div class="grid grid-cols-12 gap-6">
                <!-- BEGIN: Latest Uploads -->
                <div class="intro-y box col-span-12 lg:col-span-12">
                    <div class="p-8">
                        <form action="{{route('users.add')}}" method="post">
                            @csrf
                            <input type="hidden" name="id" value="{{ $user->id }}">
                            <div class="grid grid-cols-12 mt-4">
                                <div class="col-span-12 lg:col-span-4">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" id="full_name" class="form-control" name="full_name"
                                           placeholder="Full Name" value="{{ $user->full_name }}" required>
                                </div>
                                <div class="col-span-12 lg:col-span-4 mr-2  ml-2">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" id="email" class="form-control" name="email" placeholder="Email"
                                           value="{{ $user->email }}" required>
                                </div>

                                <div class="col-span-12 lg:col-span-4">
                                    <label for="phone_number" class="form-label">Phone No</label>
                                    <input type="tel" id="phone_number" class="form-control" name="phone_number"
                                           value="{{ $user->phone_number }}" placeholder="Phone No" required>
                                </div>
                            </div>

                            @php
                                $sector = $user->sector();
                            @endphp
                            <div class="grid grid-cols-12 mt-4">
                                <div class="col-span-12 lg:col-span-4 mr-">
                                    <label for="regular-form-2" class="form-label">Sector</label>
                                    <select name="role" id="" class="form-control">
                                        <option value="">Select</option>
                                        <option
                                            {{ $user->role()->role == 'Governor'? 'selected' : '' }}
                                            value="Governor"> Governor
                                        </option>
                                        <option
                                            {{ $user->role()->role == 'System Admin'? 'selected' : '' }}
                                            value="System Admin"> System Admin
                                        </option>
                                        <option
                                            {{ $user->role()->role == 'Sector Head'? 'selected' : '' }}
                                            value="Sector Head"> Sector Head
                                        </option>
                                        <option {{ $user->role()->role == 'Sector Admin'? 'selected' : '' }}
                                                value="Sector Admin">Sector Admin
                                        </option>
                                    </select>
                                </div>

                                <div class="col-span-12 lg:col-span-4 ml-1">
                                    <label for="regular-form-2" class="form-label">Sector</label>
                                    <select name="sector_id" id="" class="form-control">
                                        <option value="">Select</option>
                                        @foreach($sectors as $sektor)
                                            <option {{ $sector->id===$sektor->id? 'selected':'' }}
                                                    value="{{$sektor->id}}">{{$sektor->sector_name}}
                                            </option>
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
                <!-- END: Latest Uploads -->
            </div>
        </div>

        <div id="settings" class="tab-pane" role="tabpanel" aria-labelledby="settings-tab">
            <div class="grid grid-cols-12 gap-6">
                <!-- BEGIN: Latest Uploads -->
                <div class="intro-y box col-span-12 lg:col-span-4">
                    {{--                    <div class="p-5">--}}
                    {{--                        <div class="flex items-center">--}}
                    {{--                            <div class="file"><a href="" class="w-12 file__icon file__icon--directory"></a></div>--}}
                    {{--                            <div class="ml-4">--}}
                    {{--                                <a class="font-medium" href="">Documenta678tion</a>--}}
                    {{--                                <div class="text-slate-500 text-xs mt-0.5">40 KB</div>--}}
                    {{--                            </div>--}}
                    {{--                        </div>--}}
                    {{--                    </div>--}}
                </div>
                <!-- END: Latest Uploads -->
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{asset('dist/js/jquery.min.js')}}"></script>
    <script>
        $(function () {
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
