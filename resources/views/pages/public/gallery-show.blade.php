<!DOCTYPE html>
<html lang="en" class="light">
<!-- BEGIN: Head -->
<head>
    <meta charset="utf-8">
    <link href="{{asset('jg_logo.png')}}" rel="shortcut icon">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
          content="{{ $gallery->caption ?? 'Image Details' }} - Photo Gallery - Performance Delivery Coordination Unit (PDCU)">
    <meta name="keywords"
          content="gallery, photos, images, PDCU, Performance Delivery Coordination Unit">
    <meta name="author" content="PDCU">
    <title>{{ $gallery->title ?? 'Image Details' }} - Photo Gallery - Performance Delivery Coordination Unit (PDCU)</title>
    <!-- BEGIN: CSS Assets-->
    <link rel="stylesheet" href="{{asset('dist/css/app.css')}}"/>
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

        body {
            font-family: 'Public Sans', sans-serif;
        }
    </style>
    <!-- END: CSS Assets-->
</head>
<!-- END: Head -->
<body class="py-5 md:py-0 bg-background-light">
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
    class="top-bar-boxed top-bar-boxed--top-menu h-[70px] md:h-[65px] z-[51] border-b border-white/[0.08] mt-12 md:mt-0 -mx-3 sm:-mx-8 md:-mx-0 px-3 md:border-b-0 relative md:fixed md:inset-x-0 md:top-0 sm:px-8 md:px-10 md:pt-10 md:bg-gradient-to-b md:from-slate-100 md:to-transparent">
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
    <main class="flex-grow flex flex-col items-center justify-center p-4 lg:p-8 relative overflow-hidden">
        <!-- Background Glow -->
        <div
            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-primary/5 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="w-full max-w-7xl mx-auto flex flex-col lg:flex-row gap-8 items-start relative z-10">
            <!-- Image Area -->
            <div
                class="w-full lg:flex-1 group relative bg-white rounded-xl overflow-hidden shadow-2xl ring-1 ring-primary/10">
                <!-- Navigation Arrows -->
                @if($previous)
                    <a href="{{ route('public.gallery.show', $previous->id) }}"
                       class="absolute left-4 top-1/2 -translate-y-1/2 z-20 p-3 rounded-full bg-black/40 text-white hover:bg-primary transition-all opacity-0 group-hover:opacity-100 flex items-center justify-center backdrop-blur-sm">
                        <span class="material-icons">chevron_left</span>
                    </a>
                @endif
                @if($next)
                    <a href="{{ route('public.gallery.show', $next->id) }}"
                       class="absolute right-4 top-1/2 -translate-y-1/2 z-20 p-3 rounded-full bg-black/40 text-white hover:bg-primary transition-all opacity-0 group-hover:opacity-100 flex items-center justify-center backdrop-blur-sm">
                        <span class="material-icons">chevron_right</span>
                    </a>
                @endif
                <div class="aspect-video w-full relative">
                    <img alt="{{ $gallery->title ?? 'Gallery Image' }}" class="w-full h-full object-cover"
                         src="{{ asset($gallery->image_path) }}">
                    <!-- Zoom Toggle Overlay -->
                    <div class="absolute bottom-4 right-4 flex space-x-2">
                        <a href="{{ asset($gallery->image_path) }}" target="_blank"
                           class="p-2 rounded-lg bg-black/60 text-white backdrop-blur-md hover:text-primary transition-colors">
                            <span class="material-icons text-sm">zoom_in</span>
                        </a>
                        <a href="{{ asset($gallery->image_path) }}" target="_blank"
                           class="p-2 rounded-lg bg-black/60 text-white backdrop-blur-md hover:text-primary transition-colors">
                            <span class="material-icons text-sm">fullscreen</span>
                        </a>
                    </div>
                </div>
            </div>
            <!-- Sidebar Info Area -->
            <div class="w-full lg:w-96 space-y-6">
                <div class="space-y-4">
                    <div class="flex items-center space-x-2">
                        @if($gallery->status === 'active')
                            <span
                                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-primary/20 text-primary">Active</span>
                        @endif
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900 leading-tight">
                        {{ $gallery->title ?? 'Untitled Image' }}
                    </h1>
                    @if($gallery->caption)
                        <p class="text-slate-600 leading-relaxed text-sm">
                            {{ $gallery->caption }}
                        </p>
                    @endif
                </div>
                <div class="h-px bg-primary/10 w-full"></div>
                <!-- Meta Info List -->
                <div class="space-y-5">
                    @if($gallery->uploader)
                        <div class="flex items-start space-x-3">
                            <div
                                class="w-10 h-10 rounded-full bg-primary/10 border border-primary/20 overflow-hidden flex-shrink-0 flex items-center justify-center">
                                <span class="material-icons text-primary">person</span>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-primary">Uploaded By</p>
                                <p class="text-sm font-semibold text-slate-900">{{ $gallery->uploader->name }}</p>
                                @if($gallery->uploader->role)
                                    <p class="text-xs text-slate-500">{{ ucfirst($gallery->uploader->role->name ?? 'User') }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Upload Date</p>
                            <div class="flex items-center text-sm text-slate-700">
                                <span class="material-icons text-xs mr-1.5 text-primary">calendar_today</span>
                                {{ $gallery->created_at->format('M d, Y') }}
                            </div>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Status</p>
                            <div class="flex items-center text-sm text-slate-700">
                                <span class="material-icons text-xs mr-1.5 text-primary">check_circle</span>
                                {{ ucfirst($gallery->status) }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pt-6 space-y-3">
                    <a href="{{ route('public.gallery.index') }}"
                       class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 rounded-lg flex items-center justify-center transition-all shadow-lg shadow-primary/20">
                        <span class="material-icons mr-2">grid_view</span>
                        Back to Gallery
                    </a>
                    @if($previous || $next)
                        <div class="flex gap-2">
                            @if($previous)
                                <a href="{{ route('public.gallery.show', $previous->id) }}"
                                   class="flex-1 bg-white border border-primary/20 hover:bg-primary/10 text-primary font-semibold py-2.5 rounded-lg flex items-center justify-center transition-all">
                                    <span class="material-icons text-sm mr-1">chevron_left</span>
                                    Previous
                                </a>
                            @endif
                            @if($next)
                                <a href="{{ route('public.gallery.show', $next->id) }}"
                                   class="flex-1 bg-white border border-primary/20 hover:bg-primary/10 text-primary font-semibold py-2.5 rounded-lg flex items-center justify-center transition-all">
                                    Next
                                    <span class="material-icons text-sm ml-1">chevron_right</span>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>
</div>
<!-- END: Content -->

<!-- BEGIN: JS Assets-->
<script src="{{ asset('dist/js/app.js') }}"></script>
<!-- END: JS Assets-->
</body>
</html>
