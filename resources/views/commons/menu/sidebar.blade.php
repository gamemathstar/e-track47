<!-- BEGIN: Side Menu -->
<nav class="side-nav">
    <ul>
        <li>
            <a href="{{route('dashboard')}}" class="side-menu side-menu--active">
                <div class="side-menu__icon"> <i data-lucide="home"></i> </div>
                <div class="side-menu__title">
                    Dashboard
                </div>
            </a>
        </li>
        <li>
            <a href="javascript:;" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="box"></i> </div>
                <div class="side-menu__title">
                    Delivery Department
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
                <li>
                    <a href="{{route('sectors.index')}}" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="activity"></i> </div>
                        <div class="side-menu__title">Late </div>
                    </a>
                </li>
            </ul>
        </li>
        <li>
            <a href="javascript:;" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="box"></i> </div>
                <div class="side-menu__title">
                    Sectors
                    <div class="side-menu__sub-icon "> <i data-lucide="chevron-down"></i> </div>
                </div>
            </a>
            <ul class="">
                <li>
                    <a href="{{route('sectors.index')}}" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="activity"></i> </div>
                        <div class="side-menu__title"> All Sectors </div>
                    </a>
                </li>
                @foreach(\App\Models\Sector::get() as $sector)
                <li>
                    <a href="{{route('sectors.view',[$sector->id])}}" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="activity"></i> </div>
                        <div class="side-menu__title"> {{$sector->sector_name}} </div>
                    </a>
                </li>
                @endforeach
            </ul>
        </li>

        <li>
            <a href="{{route('users.index')}}" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="users"></i> </div>
                <div class="side-menu__title">
                    Users
                </div>
            </a>
        </li>

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
    </ul>
</nav>
<!-- END: Side Menu -->
