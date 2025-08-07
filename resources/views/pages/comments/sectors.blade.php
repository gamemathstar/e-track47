<!DOCTYPE html>
<html lang="en" class="light">
<!-- BEGIN: Head -->
<head>
    <meta charset="utf-8">
    <link href="{{asset('jg_logo.png')}}" rel="shortcut icon">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
          content="Enigma admin is super flexible, powerful, clean & modern responsive tailwind admin template with unlimited possibilities.">
    <meta name="keywords"
          content="admin template, Enigma Admin Template, dashboard template, flat admin template, responsive admin template, web app">
    <meta name="author" content="LEFT4CODE">
    <title>Performance Delivery Coordination Unit (PDCU</title>
    <!-- BEGIN: CSS Assets-->
    <link rel="stylesheet" href="dist/css/app.css"/>
    <!-- END: CSS Assets-->
</head>
<!-- END: Head -->
<body class="py-5 md:py-0">
<!-- BEGIN: Mobile Menu -->
<div class="mobile-menu md:hidden">
    <div class="mobile-menu-bar">
        <a href="" class="flex mr-auto">
            <img alt="Performance Delivery Coordination Unit (PDCU" class="w-6" src="{{asset('jg_logo.png')}}">
        </a>
        <a href="javascript:;" class="mobile-menu-toggler">
            <i data-lucide="bar-chart-2" class="w-8 h-8 text-white transform -rotate-90"></i>
        </a>
    </div>
    <div class="scrollable">
        <a href="javascript:;" class="mobile-menu-toggler"> <i data-lucide="x-circle"
                                                               class="w-8 h-8 text-white transform -rotate-90"></i> </a>
        <ul class="scrollable__content py-2">
            <li>
                <a href="{{ route('home') }}" class="menu">
                    <div class="menu__icon"><i data-lucide="home"></i></div>
                    <div class="menu__title"> Home</div>
                </a>
            </li>
            <li>
                <a href="{{ route('login') }}" class="menu">
                    <div class="menu__icon"><i data-lucide="lock"></i></div>
                    <div class="menu__title"> Login</div>
                </a>
            </li>
        </ul>
    </div>
</div>
<!-- END: Mobile Menu -->
<!-- BEGIN: Top Bar -->
<div
    class="top-bar-boxed top-bar-boxed--top-menu h-[70px] md:h-[65px] z-[51] border-b border-white/[0.08] mt-12 md:mt-0 -mx-3 sm:-mx-8 md:-mx-0 px-3 md:border-b-0 relative md:fixed md:inset-x-0 md:top-0 sm:px-8 md:px-10 md:pt-10 md:bg-gradient-to-b md:from-slate-100 md:to-transparent dark:md:from-darkmode-700">
    <div class="h-full flex items-center">
        <!-- BEGIN: Logo -->
        <a href="" class="logo -intro-x hidden md:flex xl:w-[180px] block">
            <img alt="Midone - HTML Admin Template" class="logo__image w-6" src="{{asset('jg_logo.png')}}">
            <span class="logo__text text-white text-lg ml-3"> Performance Delivery Coordination Unit (PDCU </span>
        </a>
        <!-- END: Logo -->
    </div>
</div>
<!-- END: Top Bar -->
<!-- BEGIN: Top Menu -->
<nav class="top-nav">
    <ul>
        <li>
            <a href="{{ route('home') }}" class="top-menu top-menu--active">
                <div class="top-menu__icon"><i data-lucide="home"></i></div>
                <div class="top-menu__title"> Home</div>
            </a>
        </li>
        <li>
            <a href="{{ route('login') }}" class="top-menu">
                <div class="top-menu__icon"><i data-lucide="lock"></i></div>
                <div class="top-menu__title"> Login</div>
            </a>
        </li>
    </ul>
</nav>
<!-- END: Top Menu -->
<!-- BEGIN: Content -->
<div class="content content--top-nav">
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">MDAs</h2>
    </div>
    <div class="intro-y grid grid-cols-12 gap-6 mt-5">
        <!-- BEGIN: Blog Layout -->
        @foreach($sectors as $sector)
            @php
                //                $sector = $user->sector();
            @endphp

            <div class="intro-y col-span-12 md:col-span-6">
                <div class="box">
                    <div class="flex flex-col lg:flex-row items-center p-5">
                        <div class="w-24 h-24 lg:w-12 lg:h-12 image-fit lg:mr-1">
                            <img alt="User Photo" class="rounded-full"
                                 src="{{ asset('dist/images/profile-5.jpg') }}">
                        </div>
                        <div class="lg:ml-2 lg:mr-auto text-center lg:text-left mt-3 lg:mt-0">
                            <a href="" class="font-medium">{{ $sector->sector_name }}</a>
                            <div class="text-slate-500 text-xs mt-0.5">
                                {{ $sector->description }}
                            </div>
                        </div>
                        <div class="flex mt-4 lg:mt-0">
                            <a class="btn btn-primary py-1 px-2 mr-2" href="{{route('public.mda.details',[$sector->id])}}">
                                View MDA
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
        <!-- END: Blog Layout -->
    </div>
</div>
<!-- END: Content -->

<!-- BEGIN: JS Assets-->
<script src="dist/js/app.js"></script>
<!-- END: JS Assets-->
</body>
</html>
