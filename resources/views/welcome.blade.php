<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <link href="{{asset('jg_logo.png')}}" rel="shortcut icon">
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>PDCU Management System | Jigawa State</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&amp;display=swap"
          rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet"/>
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
    <style>
        body {
            font-family: 'Public Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 antialiased font-display">
<!-- Header -->
<header
    class="sticky top-0 z-50 w-full border-b border-primary/10 bg-white/80 dark:bg-background-dark/80 backdrop-blur-md">
    <div class="container mx-auto flex h-20 items-center justify-between px-6 lg:px-12">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10">
                <img alt="Jigawa State Crest" class="h-10 w-10 object-contain"
                     src="{{asset('jg_logo.png')}}">
            </div>
            <div class="flex flex-col">
                <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white leading-none">PDCU</h1>
                <p class="text-[10px] font-semibold uppercase tracking-widest text-primary">Performance Delivery
                    Coordination Unit</p>
            </div>
        </div>
        <nav class="hidden items-center gap-8 md:flex">
            <a class="text-sm font-medium text-primary border-b-2 border-primary pb-1"
               href="{{ route('home') }}">Home</a>
            <a class="text-sm font-medium text-slate-600 hover:text-primary dark:text-slate-300 dark:hover:text-primary transition-colors"
               href="{{ route('public.gallery.index') }}">Public Gallery</a>
            {{--            <a class="text-sm font-medium text-slate-600 hover:text-primary dark:text-slate-300 dark:hover:text-primary transition-colors"--}}
            {{--               href="{{ route('reports.index') }}">Reporting</a>--}}
            {{--            <a class="text-sm font-medium text-slate-600 hover:text-primary dark:text-slate-300 dark:hover:text-primary transition-colors"--}}
            {{--               href="#">Resources</a>--}}
        </nav>
        <div class="flex items-center gap-4">
            <a href="{{ route('login') }}"
               class="flex items-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all">
                <span class="material-icons text-sm">login</span>
                Login
            </a>
        </div>
    </div>
</header>
<main>
    <!-- Hero Section -->
    <section class="relative h-[600px] w-full overflow-hidden">
        <div
            class="absolute inset-0 bg-gradient-to-r from-background-dark/90 via-background-dark/60 to-transparent z-10"></div>
        <img alt="Jigawa State Infrastructure" class="h-full w-full object-cover object-center"
             src="{{asset('jigawa3_map.png')}}">
        <div class="container relative z-20 mx-auto flex h-full flex-col justify-center px-6 lg:px-12">
            <div class="max-w-2xl">
                <div
                    class="mb-4 inline-flex items-center gap-2 rounded-full bg-primary/20 px-4 py-1.5 backdrop-blur-sm">
                    <span class="h-2 w-2 rounded-full bg-primary"></span>
                    <span class="text-xs font-bold uppercase tracking-wider text-white">Government Accountability</span>
                </div>
                <h2 class="text-5xl font-extrabold leading-tight text-white lg:text-6xl">
                    Performance <span class="text-primary">Delivery</span> Coordination Unit
                </h2>
                <p class="mt-6 text-lg text-slate-300 leading-relaxed">
                    Driving excellence in governance through data-driven tracking, performance analysis, and
                    institutional transparency for the Jigawa State Development Agenda.
                </p>
                <div class="mt-10 flex flex-wrap gap-4">
                    <a href="{{ route('reports.index') }}"
                       class="rounded-lg bg-primary px-8 py-4 text-sm font-bold text-white shadow-xl hover:translate-y-[-2px] transition-all">
                        View Performance Reports
                    </a>
                    <a href="{{ route('public.gallery.index') }}"
                       class="rounded-lg bg-white/10 px-8 py-4 text-sm font-bold text-white backdrop-blur-md border border-white/20 hover:bg-white/20 transition-all">
                        Learn More
                    </a>
                </div>
            </div>
        </div>
    </section>
    <!-- Welcome & Core Pillars -->
    <section class="py-24 bg-white dark:bg-background-dark/50">
        <div class="container mx-auto px-6 lg:px-12">
            <div class="grid grid-cols-1 gap-16 lg:grid-cols-2 lg:items-center">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-[0.2em] text-primary">Mission Statement</h3>
                    <h4 class="mt-2 text-4xl font-bold text-slate-900 dark:text-white">Enhancing Governance for the
                        People of Jigawa</h4>
                    <p class="mt-6 text-lg text-slate-600 dark:text-slate-400 leading-relaxed">
                        The PDCU is the central mechanism for monitoring and evaluating government performance. Our
                        mission is to ensure that every public project and initiative translates into tangible benefits
                        for the citizens of Jigawa State.
                    </p>
                    <div class="mt-8 space-y-4">
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                <span class="material-icons text-xl">check_circle</span>
                            </div>
                            <div>
                                <h5 class="font-bold text-slate-900 dark:text-white">Accountability</h5>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Holding departments responsible
                                    for set KPIs and milestones.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                <span class="material-icons text-xl">analytics</span>
                            </div>
                            <div>
                                <h5 class="font-bold text-slate-900 dark:text-white">Transparency</h5>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Public access to performance data
                                    and project statuses.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <div class="absolute -inset-4 rounded-xl bg-primary/5"></div>
                    <img alt="PDCU Logo" class="relative rounded-xl shadow-2xl w-full min-h-[320px] object-contain"
                         src="{{asset('jg_logo.png')}}">
                    <div class="absolute -bottom-6 -right-6 left-6 md:left-auto flex flex-wrap gap-3 justify-center md:justify-end">
                        <div class="rounded-xl bg-white dark:bg-slate-800 px-5 py-4 shadow-xl border border-primary/10">
                            <div class="text-2xl font-bold text-primary">{{ $commitmentsCount ?? 0 }}</div>
                            <div class="text-xs font-semibold text-slate-500 uppercase">Commitments</div>
                        </div>
                        <div class="rounded-xl bg-white dark:bg-slate-800 px-5 py-4 shadow-xl border border-primary/10">
                            <div class="text-2xl font-bold text-primary">{{ $deliverablesCount ?? 0 }}</div>
                            <div class="text-xs font-semibold text-slate-500 uppercase">Deliverables</div>
                        </div>
                        <div class="rounded-xl bg-white dark:bg-slate-800 px-5 py-4 shadow-xl border border-primary/10">
                            <div class="text-2xl font-bold text-primary">{{ $kpisCount ?? 0 }}</div>
                            <div class="text-xs font-semibold text-slate-500 uppercase">KPIs</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Public Gallery Preview -->
    <section class="py-24 bg-background-light dark:bg-background-dark">
        <div class="container mx-auto px-6 lg:px-12">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-[0.2em] text-primary">Transparency Hub</h3>
                    <h4 class="mt-2 text-4xl font-bold text-slate-900 dark:text-white">Project Gallery</h4>
                </div>
                <a class="flex items-center gap-2 text-primary font-semibold hover:underline"
                   href="{{ route('public.gallery.index') }}">
                    View All Projects <span class="material-icons">arrow_forward</span>
                </a>
            </div>
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                @forelse($galleries as $gallery)
                    <a href="{{ route('public.gallery.show', $gallery->id) }}"
                       class="group overflow-hidden rounded-xl bg-white dark:bg-slate-800 shadow-sm border border-slate-200 dark:border-slate-700 hover:shadow-xl transition-all cursor-pointer">
                        <div class="relative h-48 w-full overflow-hidden">
                            <img alt="{{ $gallery->title ?? 'Gallery Image' }}"
                                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                                 src="{{ asset($gallery->image_path) }}">
                            <span
                                class="absolute top-4 right-4 rounded-full bg-primary/90 px-3 py-1 text-[10px] font-bold text-white uppercase">Active</span>
                        </div>
                        <div class="p-6">
                            <h5 class="text-lg font-bold text-slate-900 dark:text-white">{{ $gallery->title ?? 'Untitled' }}</h5>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                                {{ $gallery->caption ? Str::limit($gallery->caption, 100) : 'No description available.' }}
                            </p>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full text-center py-12">
                        <p class="text-slate-600 dark:text-slate-400">No gallery items available at the moment.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
    <!-- CTA Section -->
    <section class="py-24">
        <div class="container mx-auto px-6 lg:px-12">
            <div class="relative overflow-hidden rounded-2xl bg-primary px-8 py-16 shadow-2xl lg:px-24">
                <div class="absolute right-0 top-0 -mr-24 -mt-24 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute left-0 bottom-0 -ml-24 -mb-24 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
                <div class="relative z-10 flex flex-col items-center text-center lg:items-start lg:text-left">
                    <h2 class="text-3xl font-bold text-white lg:text-4xl">Ready to access the dashboard?</h2>
                    <p class="mt-4 max-w-xl text-lg text-primary-100/80 text-white/80">
                        Authorized personnel can log in to view real-time performance metrics, submit reports, and
                        manage departmental KPIs.
                    </p>
                    <div class="mt-10 flex flex-wrap gap-4">
                        @auth
                            <a href="{{ route('dashboard') }}"
                               class="rounded-lg bg-white px-8 py-4 text-sm font-bold text-primary shadow-lg hover:bg-slate-100 transition-all">
                                Go to Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="rounded-lg bg-white px-8 py-4 text-sm font-bold text-primary shadow-lg hover:bg-slate-100 transition-all">
                                Administrative Login
                            </a>
                        @endauth
{{--                        <a href="{{ route('reports.index') }}"--}}
{{--                           class="rounded-lg bg-primary/30 border border-white/30 px-8 py-4 text-sm font-bold text-white backdrop-blur-sm hover:bg-primary/40 transition-all">--}}
{{--                            Public Statistics--}}
{{--                        </a>--}}
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<!-- Footer -->
<footer class="border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark py-16">
    <div class="container mx-auto px-6 lg:px-12">
        <div class="grid grid-cols-1 gap-12 md:grid-cols-2 lg:grid-cols-4">
            <div class="col-span-1 lg:col-span-1">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 bg-primary rounded-md flex items-center justify-center">
                        <span class="material-icons text-white text-sm">bar_chart</span>
                    </div>
                    <span class="text-lg font-bold text-slate-900 dark:text-white">PDCU Jigawa</span>
                </div>
                <p class="mt-4 text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    The official Performance Delivery Coordination Unit of the Jigawa State Government. Dedicated to
                    excellence and accountability.
                </p>
            </div>
            <div>
                <h6 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Quick Links</h6>
                <ul class="mt-6 space-y-3">
                    <li><a class="text-sm text-slate-600 dark:text-slate-400 hover:text-primary transition-colors"
                           href="{{ route('home') }}">Home Page</a></li>
                    <li><a class="text-sm text-slate-600 dark:text-slate-400 hover:text-primary transition-colors"
                           href="#">State Profile</a></li>
                    <li><a class="text-sm text-slate-600 dark:text-slate-400 hover:text-primary transition-colors"
                           href="{{ route('sectors.index') }}">MDAs List</a></li>
                    <li><a class="text-sm text-slate-600 dark:text-slate-400 hover:text-primary transition-colors"
                           href="{{ route('reports.index') }}">Public Reports</a></li>
                </ul>
            </div>
            <div>
                <h6 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Support</h6>
                <ul class="mt-6 space-y-3">
                    <li><a class="text-sm text-slate-600 dark:text-slate-400 hover:text-primary transition-colors"
                           href="#">Help Center</a></li>
                    <li><a class="text-sm text-slate-600 dark:text-slate-400 hover:text-primary transition-colors"
                           href="#">Privacy Policy</a></li>
                    <li><a class="text-sm text-slate-600 dark:text-slate-400 hover:text-primary transition-colors"
                           href="#">Terms of Service</a></li>
                    <li><a class="text-sm text-slate-600 dark:text-slate-400 hover:text-primary transition-colors"
                           href="#">Contact Admin</a></li>
                </ul>
            </div>
            <div>
                <h6 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Contact Info</h6>
                <div class="mt-6 space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="material-icons text-primary text-sm mt-1">location_on</span>
                        <span class="text-sm text-slate-600 dark:text-slate-400">Government House, Dutse, Jigawa State, Nigeria.</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="material-icons text-primary text-sm">email</span>
                        <span class="text-sm text-slate-600 dark:text-slate-400">info@pdcu.jg.gov.ng</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="material-icons text-primary text-sm">phone</span>
                        <span class="text-sm text-slate-600 dark:text-slate-400">+234 (0) 64 245 000</span>
                    </div>
                </div>
            </div>
        </div>
        <div
            class="mt-16 flex flex-col items-center justify-between border-t border-slate-200 dark:border-slate-800 pt-8 md:flex-row">
            <p class="text-xs text-slate-500 dark:text-slate-500">
                © {{ date('Y') }} Jigawa State PDCU. All rights reserved.
            </p>
            <div class="mt-4 flex gap-6 md:mt-0">
                <a class="text-slate-400 hover:text-primary transition-colors" href="#"><span class="material-icons">facebook</span></a>
                <a class="text-slate-400 hover:text-primary transition-colors" href="#">
                    <svg class="h-5 w-5 fill-current" viewbox="0 0 24 24">
                        <path
                            d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</footer>
</body>
</html>
