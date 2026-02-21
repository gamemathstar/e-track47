@extends("layouts.app")

@section('css')
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&amp;display=swap"
          rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#008751",
                        "background-light": "#f6f6f8",
                        "background-dark": "#101622",
                    },
                    fontFamily: {
                        "display": ["Public Sans"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        .material-icons {
            font-family: 'Material Icons';
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
            -webkit-font-feature-settings: 'liga';
            -webkit-font-smoothing: antialiased;
        }

        .content {
            font-family: 'Public Sans', sans-serif;
        }
    </style>
@endsection

@section('content')
    <div class="p-6 space-y-6">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">System Users</h2>
                <p class="text-sm text-slate-600 mt-1">Manage system users and their roles</p>
            </div>
            <button
                class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 shadow-lg shadow-primary/20 transition-all flex items-center gap-2 text-sm font-bold"
                data-tw-toggle="modal" data-tw-target="#addUserModal">
                <span class="material-icons text-sm">person_add</span>
                Add New User
            </button>
        </div>

        <!-- Users Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($users as $user)
                @php
                    $sector = $user->sector();
                @endphp
                <div
                    class="bg-white rounded-xl border border-primary/10 shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start gap-4">
                            <div
                                class="w-16 h-16 rounded-full overflow-hidden border-2 border-primary/20 flex-shrink-0">
                                <img alt="User Photo" class="w-full h-full object-cover"
                                     src="{{ asset($user->image_url ? 'uploads/users/' . $user->image_url : 'dist/images/profile-5.jpg') }}">
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-bold text-slate-900 truncate">{{ $user->full_name }}</h3>
                                <p class="text-sm text-slate-600 mt-1">
                                    {{ $user->role() ? $user->role()->role : 'No Role' }}
                                </p>
                                @if($sector)
                                    <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                        <span class="material-icons text-sm">business</span>
                                        {{ $sector->sector_name }}
                                    </p>
                                @endif
                                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                    <span class="material-icons text-sm">email</span>
                                    <span class="truncate">{{ $user->email }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-primary/10 space-y-2">
                            <a href="{{route('users.view',[$user->id])}}"
                               class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-all flex items-center justify-center gap-2 text-sm font-medium">
                                <span class="material-icons text-sm">visibility</span>
                                View Details
                            </a>
                            @php
                                $currentUser = \Illuminate\Support\Facades\Auth::user();
                                $userCurrentRole = $user->getCurrentRole();
                            @endphp
                            @if($currentUser && $currentUser->isSystemAdmin() && $userCurrentRole && $userCurrentRole->isActive())
                                <form action="{{ route('users.role.revoke', $user) }}" method="post" class="w-full">
                                    @csrf
                                    <input type="hidden" name="role_id" value="{{ $userCurrentRole->id }}">
                                    <button type="submit" 
                                            onclick="return confirm('Are you sure you want to revoke {{ $user->full_name }}\'s role ({{ $userCurrentRole->role }})?')"
                                            class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all flex items-center justify-center gap-2 text-sm font-medium">
                                        <span class="material-icons text-sm">block</span>
                                        Revoke Role
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if(count($users) == 0)
            <div class="bg-white rounded-xl border border-primary/10 p-12 text-center">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-icons text-3xl text-primary">people_outline</span>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">No Users Found</h3>
                <p class="text-sm text-slate-600 mb-6">Get started by adding your first system user.</p>
                <button
                    class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 shadow-lg shadow-primary/20 transition-all flex items-center gap-2 text-sm font-bold mx-auto"
                    data-tw-toggle="modal" data-tw-target="#addUserModal">
                    <span class="material-icons text-sm">person_add</span>
                    Add New User
                </button>
            </div>
        @endif
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="text-xl font-bold text-slate-900">Add New User</h2>
                </div>
                <div class="modal-body">
                    <form action="{{route('users.add')}}" method="post">
                        @csrf
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-slate-700 mb-2">Full
                                        Name</label>
                                    <input type="text" id="full_name" name="full_name"
                                           class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                           placeholder="Enter full name" required>
                                </div>
                                <div>
                                    <label for="email"
                                           class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                                    <input type="email" id="email" name="email"
                                           class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                           placeholder="Enter email address" required>
                                </div>
                                <div>
                                    <label for="phone_number" class="block text-sm font-medium text-slate-700 mb-2">Phone
                                        Number</label>
                                    <input type="tel" id="phone_number" name="phone_number"
                                           class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                           placeholder="Enter phone number" required>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="role" class="block text-sm font-medium text-slate-700 mb-2">User
                                        Type</label>
                                    <select name="role" id="role"
                                            class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
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

                                <div class="hidden" id="sectorArea">
                                    <label for="sector"
                                           class="block text-sm font-medium text-slate-700 mb-2">Sector</label>
                                    <select name="sector_id" id="sector"
                                            class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                                        <option value="">Select Sector</option>
                                        @foreach($sectors as $sektor)
                                            <option value="{{$sektor->id}}">{{$sektor->sector_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-3 pt-6 border-t border-slate-200">
                            <button type="button" data-tw-dismiss="modal"
                                    class="px-6 py-2.5 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-all text-sm font-medium">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-6 py-2.5 bg-primary text-white rounded-lg hover:bg-primary/90 shadow-lg shadow-primary/20 transition-all text-sm font-bold">
                                Save User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{asset('dist/js/jquery.min.js')}}"></script>
    <script>
        $(function () {
            const addUserModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#addUserModal"));

            $("#role").on('change', function () {
                var selectedRole = $(this).val();
                if (selectedRole === 'Sector Head' || selectedRole === 'Sector Admin' || selectedRole === 'Facilitator') {
                    $("#sectorArea").removeClass('hidden');
                    $("#sector").prop('required', true);
                } else {
                    $("#sectorArea").addClass('hidden');
                    $("#sector").prop('required', false);
                }
            });
        });
    </script>
@endsection
