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
    <title>Photo Gallery - Performance Delivery Coordination Unit (PDCU)</title>
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
            Photo Gallery
        </h2>
    </div>

    <div class="intro-y mt-5">
        @if($galleries->count() > 0)
            <div class="gallery-container" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                @foreach($galleries as $gallery)
                    <div class="box p-3 cursor-pointer hover:shadow-lg transition-shadow" 
                         onclick="window.location.href='{{ route('public.gallery.show', $gallery->id) }}'">
                        <div class="relative overflow-hidden rounded-lg mb-3" style="padding-bottom: 75%;">
                            <img src="{{ asset($gallery->image_path) }}" 
                                 alt="{{ $gallery->title ?? 'Gallery Image' }}" 
                                 class="absolute inset-0 w-full h-full object-cover">
                        </div>
                        @if($gallery->title)
                            <h3 class="font-medium text-sm mb-1">{{ $gallery->title }}</h3>
                        @endif
                        @if($gallery->caption)
                            <p class="text-xs text-gray-600 line-clamp-2">{{ Str::limit($gallery->caption, 80) }}</p>
                        @endif
                    </div>
                @endforeach
            </div>

            @if($galleries->hasPages())
                <div class="mt-8">
                    {{ $galleries->links() }}
                </div>
            @endif
        @else
            <div class="box p-10 text-center">
                <i data-lucide="image" class="w-20 h-20 mx-auto mb-4 text-gray-400"></i>
                <h3 class="text-lg font-medium mb-2">No Images Available</h3>
                <p class="text-gray-600">The gallery is currently empty. Please check back later.</p>
            </div>
        @endif
    </div>
</div>
<!-- END: Content -->

<!-- BEGIN: JS Assets-->
<script src="{{ asset('dist/js/app.js') }}"></script>
<!-- END: JS Assets-->

<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .gallery-container {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 1.5rem !important;
        width: 100% !important;
    }
    
    @media (max-width: 640px) {
        .gallery-container {
            grid-template-columns: repeat(1, 1fr) !important;
        }
    }
    
    @media (min-width: 641px) {
        .gallery-container {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }
</style>
</body>
</html>
