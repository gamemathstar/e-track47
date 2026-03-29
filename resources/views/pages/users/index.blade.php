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
        <!-- Session Messages -->
        @if(session('success'))
            <div
                class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-sm">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" class="text-emerald-600 hover:text-emerald-800"
                        onclick="this.parentElement.remove()">
                    <span class="material-icons text-sm">close</span>
                </button>
            </div>
        @endif
        @if(session('failure'))
            <div
                class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-icons text-sm">error</span>
                    <span>{{ session('failure') }}</span>
                </div>
                <button type="button" class="text-red-600 hover:text-red-800" onclick="this.parentElement.remove()">
                    <span class="material-icons text-sm">close</span>
                </button>
            </div>
        @endif
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-icons text-sm">error</span>
                    <span class="font-bold">Please fix the following errors:</span>
                </div>
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

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

        <!-- Filters Section -->
        <div class="bg-white rounded-xl border border-primary/10 shadow-sm p-4">
            <form method="GET" action="{{ route('users.index') }}" class="flex flex-col md:flex-row gap-4">
                <!-- Search Input -->
                <div class="flex-1">
                    <label for="search" class="block text-xs font-medium text-slate-700 mb-1">Search</label>
                    <div class="relative">
                        <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
                        <input type="text"
                               id="search"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Search by name or email..."
                               class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all text-sm">
                    </div>
                </div>

                <!-- Role Filter -->
                <div class="md:w-48">
                    <label for="role" class="block text-xs font-medium text-slate-700 mb-1">Role</label>
                    <select name="role"
                            id="role"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all text-sm">
                        <option value="">All Roles</option>
                        @foreach($roles ?? [] as $role)
                            <option
                                value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>{{ $role }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Sector Filter -->
                <div class="md:w-48">
                    <label for="sector_id" class="block text-xs font-medium text-slate-700 mb-1">Sector</label>
                    <select name="sector_id"
                            id="sector_id"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all text-sm">
                        <option value="">All Sectors</option>
                        @foreach($sectors as $sector)
                            <option
                                value="{{ $sector->id }}" {{ request('sector_id') == $sector->id ? 'selected' : '' }}>{{ $sector->sector_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Buttons -->
                <div class="flex items-end gap-2">
                    <button type="submit"
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-all flex items-center gap-2 text-sm font-medium">
                        <span class="material-icons text-sm">filter_list</span>
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'role', 'sector_id']))
                        <a href="{{ route('users.index') }}"
                           class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition-all flex items-center gap-2 text-sm font-medium">
                            <span class="material-icons text-sm">clear</span>
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Users Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($users as $user)
                @php
                    $userRole = $user->getCurrentRole();
                    $sector = $user->sector();
                    $facilitatorSectors = null;
                    if ($userRole && $userRole->role === 'Facilitator') {
                        $facilitatorSectors = $userRole->facilitatorSectors()->with('sector')->get();
                    }
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
                                    {{ $userRole ? $userRole->role : 'No Role' }}
                                </p>
                                @if($facilitatorSectors && $facilitatorSectors->count() > 0)
                                    <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                        <span class="material-icons text-sm">business</span>
                                        <span
                                            class="truncate">{{ $facilitatorSectors->pluck('sector.sector_name')->join(', ') }}</span>
                                    </p>
                                @elseif($sector)
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
                        <div class="mt-4 pt-4 border-t border-primary/10 flex gap-2">
                            <a href="{{route('users.view',[$user->id])}}"
                               class="flex-1 px-4 py-2 h-[42px] bg-primary text-white rounded-lg hover:bg-primary/90 transition-all flex items-center justify-center gap-2 text-sm font-medium">
                                <span class="material-icons text-sm">visibility</span>
                                View Details
                            </a>
                            @php
                                $currentUser = \Illuminate\Support\Facades\Auth::user();
                                $userCurrentRole = $user->getCurrentRole();
                            @endphp
                            @if($currentUser && $currentUser->isSystemAdmin() && $userCurrentRole && $userCurrentRole->isActive())
                                <form action="{{ route('users.role.revoke', $user) }}" method="post" class="flex-1">
                                    @csrf
                                    <input type="hidden" name="role_id" value="{{ $userCurrentRole->id }}">
                                    <button type="submit"
                                            onclick="return confirm('Are you sure you want to revoke {{ $user->full_name }}\'s role ({{ $userCurrentRole->role }})?')"
                                            class="w-full h-[42px] px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all flex items-center justify-center gap-2 text-sm font-medium">
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

        @if($users->count() == 0)
            <div class="bg-white rounded-xl border border-primary/10 p-12 text-center">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-icons text-3xl text-primary">people_outline</span>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">No Users Found</h3>
                @if(request()->hasAny(['search', 'role', 'sector_id']))
                    <p class="text-sm text-slate-600 mb-6">No users match your current filters. Try adjusting your
                        search criteria.</p>
                    <a href="{{ route('users.index') }}"
                       class="inline-block px-6 py-3 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition-all flex items-center gap-2 text-sm font-bold mx-auto">
                        <span class="material-icons text-sm">clear</span>
                        Clear Filters
                    </a>
                @else
                    <p class="text-sm text-slate-600 mb-6">Get started by adding your first system user.</p>
                    <button
                        class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 shadow-lg shadow-primary/20 transition-all flex items-center gap-2 text-sm font-bold mx-auto"
                        data-tw-toggle="modal" data-tw-target="#addUserModal">
                        <span class="material-icons text-sm">person_add</span>
                        Add New User
                    </button>
                @endif
            </div>
        @endif

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="bg-white rounded-xl border border-primary/10 shadow-sm p-4">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-slate-600">
                        Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} users
                    </div>
                    <div class="flex items-center gap-2">
                        {{ $users->links() }}
                    </div>
                </div>
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
                    <form action="{{ route('users.add') }}" method="post">
                        @csrf
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="add_full_name" class="block text-sm font-medium text-slate-700 mb-2">
                                        Full Name
                                    </label>
                                    <input type="text" id="add_full_name" name="full_name"
                                           class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all @error('full_name') border-red-500 @enderror"
                                           placeholder="Enter full name"
                                           value="{{ old('full_name') }}"
                                           required>
                                    @error('full_name')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="add_email" class="block text-sm font-medium text-slate-700 mb-2">
                                        Email
                                    </label>
                                    <input type="email" id="add_email" name="email"
                                           class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all @error('email') border-red-500 @enderror"
                                           placeholder="Enter email address"
                                           value="{{ old('email') }}"
                                           required>
                                    @error('email')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="add_phone_number" class="block text-sm font-medium text-slate-700 mb-2">
                                        Phone Number
                                    </label>
                                    <input type="tel" id="add_phone_number" name="phone_number"
                                           class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                           placeholder="Enter phone number"
                                           value="{{ old('phone_number') }}">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="add_role" class="block text-sm font-medium text-slate-700 mb-2">
                                        User Type / Role
                                    </label>
                                    <select name="role" id="add_role"
                                            class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all @error('role') border-red-500 @enderror"
                                            required>
                                        <option value="">Select Role</option>
                                        <option value="Governor" {{ old('role') == 'Governor' ? 'selected' : '' }}>
                                            Governor
                                        </option>
                                        <option
                                            value="System Admin" {{ old('role') == 'System Admin' ? 'selected' : '' }}>
                                            System Admin
                                        </option>
                                        <option
                                            value="Sector Head" {{ old('role') == 'Sector Head' ? 'selected' : '' }}>
                                            Sector Head
                                        </option>
                                        <option value="Data Admin" {{ old('role') == 'Data Admin' ? 'selected' : '' }}>
                                            Data Admin
                                        </option>
                                        <option
                                            value="Coordinator" {{ old('role') == 'Coordinator' ? 'selected' : '' }}>
                                            Coordinator
                                        </option>
                                        <option
                                            value="Deputy Coordinator" {{ old('role') == 'Deputy Coordinator' ? 'selected' : '' }}>
                                            Deputy Coordinator
                                        </option>
                                        <option
                                            value="Facilitator" {{ old('role') == 'Facilitator' ? 'selected' : '' }}>
                                            Facilitator
                                        </option>
                                    </select>
                                    @error('role')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Single Sector Selection (Sector Head, Data Admin) -->
                                <div id="sectorArea" style="display: none;">
                                    <label for="add_sector_id" class="block text-sm font-medium text-slate-700 mb-2">
                                        Sector <span class="text-red-500 text-xs">(required for this role)</span>
                                    </label>
                                    <select name="sector_id" id="add_sector_id"
                                            class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all @error('sector_id') border-red-500 @enderror">
                                        <option value="">Select Sector</option>
                                        @foreach($sectors as $sektor)
                                            <option
                                                value="{{ $sektor->id }}" {{ old('sector_id') == $sektor->id ? 'selected' : '' }}>
                                                {{ $sektor->sector_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('sector_id')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Multiple Sector Selection (Facilitator) -->
                                <div id="facilitatorSectorsArea" style="display: none;">
                                    <label for="sector_ids" class="block text-sm font-medium text-slate-700 mb-2">
                                        Sectors <span class="text-red-500 text-xs">(select one or more sectors)</span>
                                    </label>
                                    <select name="sector_ids[]" id="sector_ids" multiple
                                            class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all @error('sector_ids') border-red-500 @enderror"
                                            style="min-height: 120px;">
                                        @foreach($sectors as $sektor)
                                            <option
                                                value="{{ $sektor->id }}" {{ in_array($sektor->id, old('sector_ids', [])) ? 'selected' : '' }}>
                                                {{ $sektor->sector_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-slate-500 mt-1">Hold Ctrl (Windows) or Cmd (Mac) to select
                                        multiple sectors</p>
                                    @error('sector_ids')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                    @error('sector_ids.*')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
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
    <script>
        // Wait for modal to be shown before attaching logic
        document.addEventListener('DOMContentLoaded', function () {
            console.log("index.js loaded – waiting for modal show event");

            const modalElement = document.getElementById('addUserModal');

            if (!modalElement) {
                console.error("Modal element #addUserModal not found");
                return;
            }

            modalElement.addEventListener('shown.tw.modal', function () {
                console.log("Modal is now visible – initializing sector logic");

                const roleSelect = document.getElementById('add_role');
                const sectorWrapper = document.getElementById('sectorArea');
                const facilitatorWrapper = document.getElementById('facilitatorSectorsArea');
                const singleSectorSelect = document.getElementById('add_sector_id');
                const multiSectorSelect = document.getElementById('sector_ids');

                if (!roleSelect || !sectorWrapper || !facilitatorWrapper || !singleSectorSelect || !multiSectorSelect) {
                    console.error("One or more critical elements missing inside modal");
                    console.log("add_role:", !!roleSelect);
                    console.log("sectorArea:", !!sectorWrapper);
                    console.log("facilitatorSectorsArea:", !!facilitatorWrapper);
                    console.log("add_sector_id:", !!singleSectorSelect);
                    console.log("sector_ids:", !!multiSectorSelect);
                    return;
                }

                console.log("All elements found – logic attached");

                const singleSectorRoles = ['Sector Head', 'Data Admin'];
                const multiSectorRole = 'Facilitator';

                function updateVisibility() {
                    const value = roleSelect.value.trim();
                    console.log("Current role value:", value);

                    const showSingle = singleSectorRoles.includes(value);
                    const showMulti = value === multiSectorRole;

                    sectorWrapper.style.display = showSingle ? 'block' : 'none';
                    facilitatorWrapper.style.display = showMulti ? 'block' : 'none';

                    singleSectorSelect.required = showSingle;
                    multiSectorSelect.required = showMulti;

                    if (!showSingle) singleSectorSelect.value = '';
                    if (!showMulti) {
                        Array.from(multiSectorSelect.options).forEach(opt => opt.selected = false);
                    }
                }

                roleSelect.addEventListener('change', updateVisibility);

                // Run once immediately (handles old() values / validation errors)
                updateVisibility();
            });
        });
    </script>
@endsection
