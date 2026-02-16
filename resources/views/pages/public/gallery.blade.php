<!DOCTYPE html>
<html lang="en" class="light">
<!-- BEGIN: Head -->
<head>
    <meta charset="utf-8">
    <link href="{{asset('jg_logo.png')}}" rel="shortcut icon">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
          content="Photo Gallery - Performance Delivery Coordination Unit (PDCU)">
    <meta name="keywords"
          content="gallery, photos, images, PDCU, Performance Delivery Coordination Unit">
    <meta name="author" content="PDCU">
    <title>Photo Gallery - Performance Delivery Coordination Unit (PDCU)</title>
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

        .gallery-card:hover .overlay {
            opacity: 1;
        }

        .gallery-card:hover img {
            transform: scale(1.05);
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
    <!-- Page Introduction -->
    <div class="mb-12 text-center">
        <h2 class="text-3xl font-bold text-slate-900 mb-3">Photo Gallery</h2>
        <p class="text-slate-600 max-w-2xl mx-auto">Explore our collection of images showcasing the work and achievements of the Performance Delivery Coordination Unit.</p>
    </div>

    @if($galleries->count() > 0)
        <!-- Image Gallery Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
            @foreach($galleries as $gallery)
                <div class="gallery-card group relative bg-white rounded-xl overflow-hidden shadow-sm border border-primary/10 flex flex-col cursor-pointer"
                     onclick="window.location.href='{{ route('public.gallery.show', $gallery->id) }}'">
                    <div class="relative aspect-video overflow-hidden">
                        <img class="w-full h-full object-cover transition-transform duration-500 ease-in-out"
                             alt="{{ $gallery->title ?? 'Gallery Image' }}"
                             src="{{ asset($gallery->image_path) }}"/>
                        <div class="overlay absolute inset-0 bg-primary/40 opacity-0 transition-opacity duration-300 flex items-center justify-center">
                            <button class="bg-white text-slate-900 px-6 py-2 rounded-full font-bold shadow-lg flex items-center gap-2">
                                <span class="material-icons text-sm">visibility</span>
                                View Image
                            </button>
                        </div>
                        @if($gallery->status === 'active')
                            <div class="absolute top-4 left-4">
                                <span class="bg-primary text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Active</span>
                            </div>
                        @endif
                    </div>
                    <div class="p-6 flex justify-between items-center">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-slate-900 mb-1">{{ $gallery->title ?? 'Untitled' }}</h3>
                            @if($gallery->caption)
                                <p class="text-sm text-slate-500 flex items-center gap-1 line-clamp-2">
                                    <span class="material-icons text-xs">description</span>
                                    {{ Str::limit($gallery->caption, 80) }}
                                </p>
                            @endif
                            @if($gallery->created_at)
                                <p class="text-xs text-slate-400 mt-2 flex items-center gap-1">
                                    <span class="material-icons text-xs">calendar_today</span>
                                    {{ $gallery->created_at->format('M d, Y') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($galleries->hasPages())
            <div class="flex flex-col items-center gap-4 py-8">
                <div class="flex items-center gap-2">
                    @if($galleries->onFirstPage())
                        <button class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-400 cursor-not-allowed" disabled>
                            <span class="material-icons">chevron_left</span>
                        </button>
                    @else
                        <a href="{{ $galleries->previousPageUrl() }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50 font-medium">
                            <span class="material-icons">chevron_left</span>
                        </a>
                    @endif

                    @foreach($galleries->getUrlRange(1, $galleries->lastPage()) as $page => $url)
                        @if($page == $galleries->currentPage())
                            <button class="w-10 h-10 flex items-center justify-center rounded-lg bg-primary text-white font-bold">{{ $page }}</button>
                        @else
                            <a href="{{ $url }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50 font-medium">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if($galleries->hasMorePages())
                        <a href="{{ $galleries->nextPageUrl() }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50 font-medium">
                            <span class="material-icons">chevron_right</span>
                        </a>
                    @else
                        <button class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-400 cursor-not-allowed" disabled>
                            <span class="material-icons">chevron_right</span>
                        </button>
                    @endif
                </div>
                <p class="text-sm text-slate-500">Showing {{ $galleries->firstItem() }} to {{ $galleries->lastItem() }} of {{ $galleries->total() }} images</p>
            </div>
        @endif
    @else
        <!-- Empty State -->
        <div class="bg-white rounded-xl border border-primary/10 shadow-sm p-12 text-center">
            <div class="w-20 h-20 mx-auto mb-4 bg-primary/10 rounded-full flex items-center justify-center">
                <span class="material-icons text-4xl text-primary">image</span>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-2">No Images Available</h3>
            <p class="text-slate-600 mb-6">The gallery is currently empty. Please check back later.</p>
        </div>
    @endif
</div>
<!-- END: Content -->

<!-- BEGIN: JS Assets-->
<script src="{{ asset('dist/js/app.js') }}"></script>
<!-- END: JS Assets-->
</body>
</html>
