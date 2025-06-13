<!-- BEGIN: Side Menu -->
@php
    $user = auth()->user();

@endphp
<nav class="side-nav">
    <ul>
        @if($user->isGovernor() || $user->isSystemAdmin() || $user->isDeliveryDepartment())
            <li>
                <a href="{{route('dashboard')}}"
                   class="side-menu {{ Request::is('dashboard*') ? 'side-menu--active' : '' }}">
                    <div class="side-menu__icon"><i data-lucide="home"></i></div>
                    <div class="side-menu__title">
                        Dashboard
                    </div>
                </a>
            </li>
            <li>
                <a href="javascript:;" class="side-menu">
                    <div class="side-menu__icon"><i data-lucide="box"></i></div>
                    <div class="side-menu__title">
                        Sectors
                        <div class="side-menu__sub-icon "><i data-lucide="chevron-down"></i></div>
                    </div>
                </a>
                <ul class="">
                    <li>
                        <a href="{{route('sectors.index')}}" class="side-menu">
                            <div class="side-menu__icon"><i data-lucide="activity"></i></div>
                            <div class="side-menu__title"> All Sectors</div>
                        </a>
                    </li>
                    @foreach(\App\Models\Sector::get() as $sector)
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
        @if($user->isDeliveryDepartment())
            {{--        <li>--}}
            {{--            <a href="javascript:;" class="side-menu {{ Request::is('sectors*') ? 'side-menu--active' : '' }}">--}}
            {{--                <div class="side-menu__icon"><i data-lucide="box"></i></div>--}}
            {{--                <div class="side-menu__title">--}}
            {{--                    Delivery Department--}}
            {{--                    <div class="side-menu__sub-icon "> <i data-lucide="chevron-down"></i> </div>--}}
            {{--                </div>--}}
            {{--            </a>--}}
            {{--            <ul class="">--}}
            {{--                <li>--}}
            {{--                    <a href="{{route('delivery.awaiting.verification')}}" class="side-menu">--}}
            {{--                        <div class="side-menu__icon"> <i data-lucide="activity"></i> </div>--}}
            {{--                        <div class="side-menu__title">Confirmation </div>--}}
            {{--                    </a>--}}
            {{--                </li>--}}
            {{--            </ul>--}}
            {{--        </li>--}}
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
        @endif
        @if($sector = $user->isSectorHead())
            <li>
                <a href="{{route('sectors.view',[$sector->id])}}"
                   class="side-menu {{ Request::is('users*') ? 'side-menu--active' : '' }}">
                    <div class="side-menu__icon"><i data-lucide="users"></i></div>
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
        {{--                        <div class="side-menu__title"> All Sectors </div>--}}
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
    </ul>
</nav>
<!-- END: Side Menu -->
