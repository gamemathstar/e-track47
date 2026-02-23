@extends("layouts.app")

@section('content')
    <div class="intro-y flex items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            User Profile
        </h2>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success-soft show flex items-center mb-2 mt-5" role="alert">
            <i data-lucide="check-circle" class="w-6 h-6 mr-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-tw-dismiss="alert" aria-label="Close">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    @endif
    @if(session('failure'))
        <div class="alert alert-danger-soft show flex items-center mb-2 mt-5" role="alert">
            <i data-lucide="alert-octagon" class="w-6 h-6 mr-2"></i> {{ session('failure') }}
            <button type="button" class="btn-close" data-tw-dismiss="alert" aria-label="Close">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    @endif

    <!-- BEGIN: Profile Info -->
    <div class="intro-y box px-5 pt-5 mt-5">
        <div class="flex flex-col lg:flex-row border-b border-slate-200/60 dark:border-darkmode-400 pb-5 -mx-5">
            <div class="flex flex-1 px-5 items-center justify-center lg:justify-start">
                <div class="w-20 h-20 sm:w-24 sm:h-24 flex-none lg:w-32 lg:h-32 image-fit relative">
                    <img alt="Midone - HTML Admin Template" class="rounded-full"
                         src="{{ asset($user->image_url? 'uploads/users/' . $user->image_url: 'dist/images/profile-5.jpg') }}">
                </div>
                <div class="ml-5">
                    @php 
                        $sector = $user->sector();
                        $userRole = $user->getCurrentRole();
                    @endphp
                    <div
                        class="w-24 sm:w-40 truncate sm:whitespace-normal font-medium text-lg">{{ $user->full_name }}</div>
                    <div
                        class="text-slate-500">{{ $userRole ? $userRole->role : 'No Role' }}{{ $sector ? ' | ' . $sector->sector_name : '' }}</div>
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
                            {{ $sector?$sector->sector_name:"" }}
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
                                $userRole = $user->getCurrentRole();
                            @endphp
                            <div class="grid grid-cols-12 mt-4">
                                <div class="col-span-12 lg:col-span-4 mr-">
                                    <label for="regular-form-2" class="form-label">Role</label>
                                    <select name="role" id="" class="form-control">
                                        <option value="">Select</option>
                                        <option
                                            {{ $userRole && $userRole->role == 'Governor'? 'selected' : '' }}
                                            value="Governor"> Governor
                                        </option>
                                        <option
                                            {{ $userRole && $userRole->role == 'System Admin'? 'selected' : '' }}
                                            value="System Admin"> System Admin
                                        </option>
                                        <option
                                            {{ $userRole && $userRole->role == 'Sector Head'? 'selected' : '' }}
                                            value="Sector Head"> Sector Head
                                        </option>
                                        <option {{ $userRole && $userRole->role == 'Sector Admin'? 'selected' : '' }}
                                                value="Sector Admin">Sector Admin
                                        </option>
                                        <option {{ $userRole && $userRole->role == 'Coordinator'? 'selected' : '' }}
                                                value="Coordinator">Coordinator
                                        </option>
                                        <option {{ $userRole && $userRole->role == 'Deputy Coordinator'? 'selected' : '' }}
                                                value="Deputy Coordinator">Deputy Coordinator
                                        </option>
                                        <option {{ $userRole && $userRole->role == 'Facilitator'? 'selected' : '' }}
                                                value="Facilitator">Facilitator
                                        </option>
                                    </select>
                                </div>

                                <div class="col-span-12 lg:col-span-4 ml-1">
                                    <label for="regular-form-2" class="form-label">Sector</label>
                                    <select name="sector_id" id="" class="form-control">
                                        <option value="">Select</option>
                                        @foreach($sectors as $sektor)
                                            <option {{ $sector && $sector->id===$sektor->id? 'selected':'' }}
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
                <!-- Role Management Section -->
                <div class="intro-y box col-span-12">
                    <div class="p-8">
                        <h3 class="text-lg font-bold mb-4">Role Management</h3>
                        
                        <!-- Current Active Role -->
                        @php
                            $currentRole = $user->getCurrentRole();
                            // Sort roles: Active first, then Revoked, then by created_at DESC
                            $allRoles = $user->roles()
                                ->orderByRaw("CASE WHEN role_status = 'Active' THEN 0 ELSE 1 END")
                                ->orderBy('created_at', 'DESC')
                                ->get();
                        @endphp
                        
                        @if($currentRole)
                            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="font-semibold text-emerald-900">Current Active Role</h4>
                                        <p class="text-sm text-emerald-700 mt-1">
                                            <strong>Role:</strong> {{ $currentRole->role }}<br>
                                            <strong>Entity:</strong> {{ $currentRole->target_entity }}
                                            @if($currentRole->target_entity === 'Sector' && $currentRole->sector)
                                                - {{ $currentRole->sector->sector_name }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 bg-emerald-600 text-white rounded-full text-xs font-bold">
                                        Active
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                                <p class="text-amber-800">No active role assigned to this user.</p>
                            </div>
                        @endif

                        <!-- Update Role Form -->
                        <div class="mb-6">
                            <h4 class="font-semibold mb-3">Update Role</h4>
                            <form action="{{ route('users.role.update', $user) }}" method="post">
                                @csrf
                                <div class="grid grid-cols-12 gap-4">
                                    <div class="col-span-12 lg:col-span-4">
                                        <label for="update_role" class="form-label">New Role</label>
                                        <select name="role" id="update_role" class="form-control" required>
                                            <option value="">Select Role</option>
                                            <option value="Governor">Governor</option>
                                            <option value="System Admin">System Admin</option>
                                            <option value="Sector Head">Sector Head</option>
                                            <option value="Sector Admin">Sector Admin</option>
                                            <option value="Coordinator">Coordinator</option>
                                            <option value="Deputy Coordinator">Deputy Coordinator</option>
                                            <option value="Facilitator">Facilitator</option>
                                        </select>
                                    </div>
                                    <div class="col-span-12 lg:col-span-4" id="update_sector_area" style="display: none;">
                                        <label for="update_sector_id" class="form-label">Sector</label>
                                        <select name="sector_id" id="update_sector_id" class="form-control">
                                            <option value="">Select Sector</option>
                                            @foreach($sectors as $sektor)
                                                <option value="{{$sektor->id}}">{{$sektor->sector_name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-span-12 lg:col-span-4 flex items-end">
                                        <button type="submit" class="btn btn-primary w-full">Update Role</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Role History -->
                        @if($allRoles && $allRoles->count() > 0)
                            <div>
                                <h4 class="font-semibold mb-3">Role History</h4>
                                <div class="overflow-x-auto">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Role</th>
                                                <th>Target Entity</th>
                                                <th>Sector</th>
                                                <th>Status</th>
                                                <th>Assigned Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($allRoles as $role)
                                                <tr>
                                                    <td>{{ $role->role }}</td>
                                                    <td>{{ $role->target_entity }}</td>
                                                    <td>
                                                        @if($role->target_entity === 'Sector' && $role->sector)
                                                            {{ $role->sector->sector_name }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($role->isActive())
                                                            <span class="badge bg-success">Active</span>
                                                        @else
                                                            <span class="badge bg-danger">Revoked</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $role->created_at ? $role->created_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                                    <td>
                                                        @if($role->isRevoked())
                                                            <form action="{{ route('users.role.reactivate', $user) }}" method="post" class="d-inline">
                                                                @csrf
                                                                <input type="hidden" name="role_id" value="{{ $role->id }}">
                                                                <button type="submit" class="btn btn-sm btn-success">Reactivate</button>
                                                            </form>
                                                        @elseif($role->isActive() && $allRoles->where('role_status', 'Active')->count() > 1)
                                                            <form action="{{ route('users.role.revoke', $user) }}" method="post" class="d-inline">
                                                                @csrf
                                                                <input type="hidden" name="role_id" value="{{ $role->id }}">
                                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to revoke this role?')">Revoke</button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
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
            // Role change handler for edit profile
            $("select[name='role']").on('change', function () {
                var selectedRole = $(this).val();
                if (selectedRole === 'Sector Head' || selectedRole === 'Sector Admin' || selectedRole === 'Facilitator') {
                    $("#sectorArea").show();
                } else {
                    $("#sectorArea").hide();
                }
            });

            // Role change handler for role update form
            $("#update_role").on('change', function () {
                var selectedRole = $(this).val();
                if (selectedRole === 'Sector Head' || selectedRole === 'Sector Admin' || selectedRole === 'Facilitator') {
                    $("#update_sector_area").show();
                    $("#update_sector_id").prop('required', true);
                } else {
                    $("#update_sector_area").hide();
                    $("#update_sector_id").prop('required', false);
                }
            });
        });
    </script>
@endsection
