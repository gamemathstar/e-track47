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
    <div class="grid grid-cols-12 gap-6" style="padding: 20px;">
        <div class="col-span-12 2xl:col-span-12">
            <div class="relative min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 py-8">



                <div class="max-w-7xl mx-auto p-6 pb-8 lg:p-8" style="padding: 20px;">
                    <!-- Header Section with Logo -->
                    <div class="text-center mb-12">
                        <div class="flex justify-center items-center mb-6">
                            <img src="{{ asset('jg_logo.png') }}" alt="Jigawa State Logo" class="w-20 h-20">
                            <div class="text-left">
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Jigawa State</h1>
                                <p class="text-3l text-gray-600 dark:text-gray-300">Project Monitoring System</p>
                            </div>
                        </div>
                    </div>

                    <!-- Governor's Message Section -->
                    <div class="max-w-4xl mx-auto mb-16">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
                            <!-- Governor's Picture - Full Width at Top -->
                            <div class="w-full bg-gradient-to-br from-blue-600 to-indigo-700 p-8 flex items-center justify-center">
                                <div class="text-center">
                                    <img src="{{ asset('governor.jpeg') }}" alt="Governor of Jigawa State">
                                    <h3 class="text-white text-2xl font-semibold">His Excellency</h3>
                                    <p class="text-blue-100 text-lg">Governor of Jigawa State</p>
                                </div>
                            </div>

                            <!-- Governor's Message - Full Width Below Image -->
                            <div class="w-full p-8 md:p-12">
                                <div class="mb-6">
                                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Welcome Message</h2>
                                    <div class="w-20 h-1 bg-blue-600 rounded-full"></div>
                                </div>

                                <div class="prose prose-lg dark:prose-invert max-w-none">
                                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed text-lg">
                                        As part of our administration's commitment to transparency and accountability, I am pleased to introduce the launch of our new Project Monitoring App. This innovative tool will enable real-time tracking and oversight of our state's infrastructure and development projects, ensuring that they are completed on time, within budget, and to the highest standards of quality.
                                    </p>
                                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed text-lg mt-4">
                                        The app will provide citizens with easy access to project information, progress updates, and feedback mechanisms, fostering greater public engagement and trust in our governance.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Features Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16 p-16">
                        <!-- Real-time Monitoring -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border-l-4 border-blue-500">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Real-time Monitoring</h3>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400 pl-5">Track project progress in real-time with live updates and comprehensive reporting.</p>
                        </div>

                        <!-- Transparency -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border-l-4 border-green-500">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Transparency</h3>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400 pl-5">Full visibility into project details, budgets, and timelines for public accountability.</p>
                        </div>

                        <!-- Public Engagement -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border-l-4 border-purple-500">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Public Engagement</h3>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400 pl-5">Citizen feedback and engagement mechanisms for better project outcomes.</p>
                        </div>
                    </div>

                    <!-- Call to Action -->
                    <div class="text-center" style="background-color: #6ca09e; padding: 20px;color">
                        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-8">
                            <h3 class="text-2xl font-bold mb-4">Ready to Explore Our Projects?</h3>
                            <p class="text-blue-100 mb-6">Discover the infrastructure and development projects transforming Jigawa State</p>
                            @auth
                                <a href="{{ url('/home') }}" class="inline-flex items-center px-8 py-3 bg-white text-blue-600 font-semibold rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                    Go to Dashboard
                                    <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                    </svg>
                                </a>
                            @else
                                <a href="{{ route('home2') }}" class="inline-flex items-center px-8 py-3 bg-white text-blue-600 font-semibold rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                    Get Started
                                    <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                    </svg>
                                </a>
                            @endauth
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="mt-16 text-center text-gray-500 dark:text-gray-400">
                        <p>&copy; {{ date('Y') }} Jigawa State Government. All rights reserved.</p>
                        <p class="mt-2">Performance Delivery Coordination Unit (PDCU)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- END: Content -->

<!-- BEGIN: JS Assets-->
<script src="dist/js/app.js"></script>
<!-- END: JS Assets-->
</body>
</html>
