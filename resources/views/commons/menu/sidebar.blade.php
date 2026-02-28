<!-- BEGIN: Side Menu -->
@php
    $user = auth()->user();

@endphp
<nav class="side-nav">
    @auth


    <ul>
        @if($user->isGovernor() || $user->isSystemAdmin() || $user->isDeliveryUnit() || $user->isSectorHead() || $user->isDataAdmin())
            <li>
                <a href="{{route('dashboard')}}"
                   class="side-menu {{ Request::is('dashboard*') ? 'side-menu--active' : '' }}">
                    <div class="side-menu__icon"><i data-lucide="home"></i></div>
                    <div class="side-menu__title">
                        Dashboard
                    </div>
                </a>
            </li>
        @endif
        @if($user->isGovernor() || $user->isSystemAdmin() || $user->isCoordinator() || $user->isDeputyCoordinator())
            <li>
                <a href="javascript:;" class="side-menu">
                    <div class="side-menu__icon"><i data-lucide="box"></i></div>
                    <div class="side-menu__title">
                        MDAs/Sectors
                        <div class="side-menu__sub-icon "><i data-lucide="chevron-down"></i></div>
                    </div>
                </a>
                <ul class="">
                    <li>
                        <a href="{{route('sectors.index')}}" class="side-menu">
                            <div class="side-menu__icon"><i data-lucide="activity"></i></div>
                            <div class="side-menu__title"> All MDAs/Sectors</div>
                        </a>
                    </li>
                    @php
                        $activeFramework = \App\Models\Framework::where('status', 'Active')->first();
                        $sectors = $activeFramework ? \App\Models\Sector::where('framework_id', $activeFramework->id)->get() : collect();
                    @endphp
                    @foreach($sectors as $sector)
                        <li>
                            <a href="{{route('sectors.view',[$sector->id])}}" class="side-menu">
                                <div class="side-menu__icon"><i data-lucide="activity"></i></div>
                                <div class="side-menu__title"> {{$sector->sector_name}} </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </li>
        @endif
        @if($user->isFacilitator())
            @php
                $facilitatorRole = $user->getCurrentRole();
                $facilitatorSectors = collect();
                if ($facilitatorRole && $facilitatorRole->role === \App\Models\UserRole::ROLE_FACILITATOR) {
                    $facilitatorSectors = $facilitatorRole->facilitatorSectors()->with('sector')->get()->pluck('sector')->filter();
                }
            @endphp
            @if($facilitatorSectors->count() > 0)
                <li>
                    <a href="javascript:;" class="side-menu {{ Request::is('sectors*') ? 'side-menu--active' : '' }}">
                        <div class="side-menu__icon"><i data-lucide="box"></i></div>
                        <div class="side-menu__title">
                            My Sector(s)
                            <div class="side-menu__sub-icon "><i data-lucide="chevron-down"></i></div>
                        </div>
                    </a>
                    <ul class="">
                        @foreach($facilitatorSectors as $sector)
                            <li>
                                <a href="{{route('sectors.view',[$sector->id])}}" class="side-menu">
                                    <div class="side-menu__icon"><i data-lucide="activity"></i></div>
                                    <div class="side-menu__title">{{ $sector->sector_name }}</div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endif
        @endif
        @if($user->isDeliveryUnit())
            <li>
                <a href="javascript:;" class="side-menu {{ Request::is('sectors*') ? 'side-menu--active' : '' }}">
                    <div class="side-menu__icon"><i data-lucide="box"></i></div>
                    <div class="side-menu__title">
                        Delivery Unit
                        <div class="side-menu__sub-icon "> <i data-lucide="chevron-down"></i> </div>
                    </div>
                </a>
                <ul class="">
                    <li>
                        <a href="{{route('delivery.awaiting.verification')}}" class="side-menu">
                            <div class="side-menu__icon"> <i data-lucide="activity"></i> </div>
                            <div class="side-menu__title">Confirmation </div>
                        </a>
                    </li>
                </ul>
            </li>
        @endif
        @if($user->isCoordinator() || $user->isDeputyCoordinator())
            <li>
                <a href="{{route('data-entry.index')}}"
                   class="side-menu {{ Request::is('data-entry*') ? 'side-menu--active' : '' }}">
                    <div class="side-menu__icon"><i data-lucide="key"></i></div>
                    <div class="side-menu__title">
                        Data Entry Management
                    </div>
                </a>
            </li>
        @endif
        @if($user->isCoordinator())
            <li>
                <a href="{{route('frameworks.index')}}"
                   class="side-menu {{ Request::is('frameworks*') ? 'side-menu--active' : '' }}">
                    <div class="side-menu__icon"><i data-lucide="file-text"></i></div>
                    <div class="side-menu__title">
                        Framework Management
                    </div>
                </a>
            </li>
        @endif

        @if($user->isSystemAdmin())
            <li>
                <a href="{{route('users.index')}}"
                   class="side-menu {{ Request::is('users*') ? 'side-menu--active' : '' }}">
                    <div class="side-menu__icon"><i data-lucide="users"></i></div>
                    <div class="side-menu__title">
                        Users
                    </div>
                </a>
            </li>
            <li>
                <a href="{{route('admin.gallery.index')}}"
                   class="side-menu {{ Request::is('admin/gallery*') ? 'side-menu--active' : '' }}">
                    <div class="side-menu__icon"><i data-lucide="image"></i></div>
                    <div class="side-menu__title">
                        Gallery Management
                    </div>
                </a>
            </li>
        @endif
        @if($sector = $user->isSectorHead())
            <li>
                <a href="{{route('sectors.view',[$sector->id])}}"
                   class="side-menu {{ Request::is('sectors*') ? 'side-menu--active' : '' }}">
                    <div class="side-menu__icon"><i data-lucide="box"></i></div>
                    <div class="side-menu__title">
                        My Sector
                    </div>
                </a>
            </li>
        @endif
        @if($sector = $user->isDataAdmin())
            <li>
                <a href="{{route('sectors.view',[$sector->id])}}"
                   class="side-menu {{ Request::is('sectors*') ? 'side-menu--active' : '' }}">
                    <div class="side-menu__icon"><i data-lucide="box"></i></div>
                    <div class="side-menu__title">
                        My Sector
                    </div>
                </a>
            </li>
        @endif
        {{--        <li>--}}
        {{--            <a href="javascript:;" class="side-menu">--}}
        {{--                <div class="side-menu__icon"> <i data-lucide="list"></i> </div>--}}
        {{--                <div class="side-menu__title">--}}
        {{--                    Reports--}}
        {{--                    <div class="side-menu__sub-icon "> <i data-lucide="chevron-down"></i> </div>--}}
        {{--                </div>--}}
        {{--            </a>--}}
        {{--            <ul class="">--}}
        {{--                <li>--}}
        {{--                    <a href="{{route('sectors.index')}}" class="side-menu">--}}
        {{--                        <div class="side-menu__icon"> <i data-lucide="activity"></i> </div>--}}
        {{--                        <div class="side-menu__title"> All MDAs/Sectors </div>--}}
        {{--                    </a>--}}
        {{--                </li>--}}
        {{--                @foreach(\App\Models\Sector::get() as $sector)--}}
        {{--                    <li>--}}
        {{--                        <a href="{{route('sectors.view',[$sector->id])}}" class="side-menu">--}}
        {{--                            <div class="side-menu__icon"> <i data-lucide="activity"></i> </div>--}}
        {{--                            <div class="side-menu__title"> {{$sector->name}} </div>--}}
        {{--                        </a>--}}
        {{--                    </li>--}}
        {{--                @endforeach--}}
        {{--            </ul>--}}
        {{--        </li>--}}
            <li>
                <a href="{{route('reports.index')}}"
                   class="side-menu {{ Request::is('reports*') ? 'side-menu--active' : '' }}">
                    <div class="side-menu__icon"><i data-lucide="bar-chart-2"></i></div>
                    <div class="side-menu__title">
                        Reports
                    </div>
                </a>
            </li>
        @if(!$user->isSystemAdmin())
            <li>
                <a href="{{route('users.view', [$user->id])}}"
                   class="side-menu {{ Request::is('users/view/' . $user->id) ? 'side-menu--active' : '' }}">
                    <div class="side-menu__icon"><i data-lucide="user"></i></div>
                    <div class="side-menu__title">
                        My Profile
                    </div>
                </a>
            </li>
        @endif
    </ul>
    @endauth
</nav>
<!-- END: Side Menu -->
