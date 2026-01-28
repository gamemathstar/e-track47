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
    <title>{{ $gallery->title ?? 'Image Details' }} - Photo Gallery - Performance Delivery Coordination Unit (PDCU)</title>
    <!-- BEGIN: CSS Assets-->
    <link rel="stylesheet" href="{{asset('dist/css/app.css')}}"/>
    <!-- END: CSS Assets-->
</head>
<!-- END: Head -->
<body class="py-5 md:py-0">
<!-- BEGIN: Mobile Menu -->
<div class="mobile-menu md:hidden">
    <div class="mobile-menu-bar">
        <a href="" class="flex mr-auto">
            <img alt="Performance Delivery Coordination Unit (PDCU)" class="w-6" src="{{asset('jg_logo.png')}}">
        </a>
        <a href="javascript:;" class="mobile-menu-toggler">
            <i data-lucide="bar-chart-2" class="w-8 h-8 text-white transform -rotate-90"></i>
        </a>
    </div>
    <div class="scrollable">
        <a href="javascript:;" class="mobile-menu-toggler">
            <i data-lucide="x-circle" class="w-8 h-8 text-white transform -rotate-90"></i> </a>
        <ul class="scrollable__content py-2">
            <li>
                <a href="{{ route('home') }}" class="menu">
                    <div class="menu__icon"><i data-lucide="home"></i></div>
                    <div class="menu__title"> Home</div>
                </a>
            </li>
            <li>
                <a href="{{ route('public.gallery.index') }}" class="menu">
                    <div class="menu__icon"><i data-lucide="image"></i></div>
                    <div class="menu__title"> Gallery</div>
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
            <img alt="App Logo" class="logo__image w-6" src="{{asset('jg_logo.png')}}">
            <span class="logo__text text-white text-lg ml-3"> Performance Delivery Coordination Unit (PDCU) </span>
        </a>
        <!-- END: Logo -->
    </div>
</div>
<!-- END: Top Bar -->
<!-- BEGIN: Top Menu -->
<nav class="top-nav">
    <ul>
        <li>
            <a href="{{ route('home') }}" class="top-menu">
                <div class="top-menu__icon"><i data-lucide="home"></i></div>
                <div class="top-menu__title"> Home</div>
            </a>
        </li>
        <li>
            <a href="{{ route('public.gallery.index') }}" class="top-menu top-menu--active">
                <div class="top-menu__icon"><i data-lucide="image"></i></div>
                <div class="top-menu__title"> Gallery</div>
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
        <h2 class="text-lg font-medium mr-auto">
            <a href="{{ route('public.gallery.index') }}" class="text-primary hover:underline">
                Photo Gallery
            </a>
            <span class="mx-2">/</span>
            <span>{{ $gallery->title ?? 'Image Details' }}</span>
        </h2>
    </div>

    <div class="intro-y grid grid-cols-12 gap-5 mt-5">
        <div class="col-span-12 lg:col-span-8">
            <div class="box p-5">
                <div class="relative">
                    <img src="{{ asset($gallery->image_path) }}" 
                         alt="{{ $gallery->title ?? 'Gallery Image' }}" 
                         class="w-full rounded-lg">
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4">
            <div class="box p-5">
                @if($gallery->title)
                    <h3 class="text-xl font-bold mb-3">{{ $gallery->title }}</h3>
                @endif

                @if($gallery->caption)
                    <div class="mb-4">
                        <p class="text-gray-700 whitespace-pre-wrap">{{ $gallery->caption }}</p>
                    </div>
                @endif

                <div class="border-t pt-4 mt-4">
                    <div class="text-sm text-gray-600 space-y-2">
                        <p><strong>Uploaded:</strong> {{ $gallery->created_at->format('F d, Y') }}</p>
                        @if($gallery->uploader)
                            <p><strong>By:</strong> {{ $gallery->uploader->name }}</p>
                        @endif
                    </div>
                </div>

                <div class="mt-6 flex gap-2">
                    @if($previous)
                        <a href="{{ route('public.gallery.show', $previous->id) }}" 
                           class="btn btn-secondary flex-1">
                            <i data-lucide="chevron-left" class="w-4 h-4 mr-2"></i>
                            Previous
                        </a>
                    @endif
                    @if($next)
                        <a href="{{ route('public.gallery.show', $next->id) }}" 
                           class="btn btn-secondary flex-1">
                            Next
                            <i data-lucide="chevron-right" class="w-4 h-4 ml-2"></i>
                        </a>
                    @endif
                </div>

                <div class="mt-4">
                    <a href="{{ route('public.gallery.index') }}" 
                       class="btn btn-primary w-full">
                        <i data-lucide="grid" class="w-4 h-4 mr-2"></i>
                        Back to Gallery
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- END: Content -->

<!-- BEGIN: JS Assets-->
<script src="{{ asset('dist/js/app.js') }}"></script>
<!-- END: JS Assets-->
</body>
</html>
