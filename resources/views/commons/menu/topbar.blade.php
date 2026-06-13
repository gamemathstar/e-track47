@php
    $user = auth()->user();
    $sector = $user ? $user->sector() : null;
@endphp
<!-- BEGIN: Top Bar -->
@auth


<div class="top-bar-boxed h-[70px] md:h-[65px] z-[51] border-b border-white/[0.08] mt-12 md:mt-0 -mx-3 sm:-mx-8 md:-mx-0 px-3 md:border-b-0 relative md:fixed md:inset-x-0 md:top-0 sm:px-8 md:px-10 md:pt-10 md:bg-gradient-to-b md:from-slate-100 md:to-transparent dark:md:from-darkmode-700">
    <div class="h-full flex items-center">
        <!-- BEGIN: Logo -->
        <a href="" class="logo -intro-x hidden md:flex xl:w block">
            <img alt="App Logo" class="logo__image w-6" src="{{asset('jg_logo.png')}}">
            <span class="logo__text text-white text-lg ml-3"> Performance Delivery Coordination Unit (PDCU) </span>
        </a>
        <!-- END: Logo -->
        <!-- BEGIN: Breadcrumb -->
        <nav aria-label="breadcrumb" class="-intro-x h-[45px] mr-auto">
{{--            <ol class="breadcrumb breadcrumb-light">--}}
{{--                <li class="breadcrumb-item"><a href="#">Application</a></li>--}}
{{--                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>--}}
{{--            </ol>--}}
        </nav>
        <!-- END: Breadcrumb -->
        <!-- BEGIN: Search -->
        <div class="intro-x relative mr-3 sm:mr-6">
            <div class="search hidden sm:block">
{{--                <input type="text" class="search__input form-control border-transparent" placeholder="Search...">--}}
{{--                <i data-lucide="search" class="search__icon dark:text-slate-500"></i>--}}
            </div>
            <a class="notification notification--light sm:hidden" href="">
                <i data-lucide="search" class="notification__icon dark:text-slate-500"></i> </a>

        </div>
        <!-- END: Search -->
        {{-- BEGIN: Notifications --
             Icons are inlined as SVG (not `data-lucide`) so the bell ALWAYS
             renders regardless of whether Lucide's JS has finished initialising
             on the host page — which was the root cause of the bell being
             invisible on some pages (any page with a JS error before
             lucide.createIcons() ran left the <i> empty). Same fix applied to
             the per-item icons inside the dropdown. --}}
        @php($topbarNotifications = $topbarNotifications ?? ['unreadCount' => 0, 'unreadLabel' => '0', 'recent' => [], 'indexUrl' => route('notifications.index'), 'markAllReadUrl' => route('notifications.mark-all-read')])
        <div class="intro-x dropdown mr-4 sm:mr-6 relative">
            <div class="dropdown-toggle cursor-pointer relative inline-flex items-center justify-center w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 transition-colors text-white"
                 role="button" aria-expanded="false" data-tw-toggle="dropdown" aria-label="Notifications"
                 style="color: #ffffff;">
                {{-- Lucide "bell" inlined. stroke="currentColor" inherits from the
                     wrapper above (text-white + inline style fallback) so the
                     icon stays bright-white on any topbar background colour. --}}
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                    <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                    <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
                </svg>
                @if ($topbarNotifications['unreadCount'] > 0)
                    {{-- Numeric unread badge. Inline style on background +
                         text colour for hard guarantee — Tailwind utility
                         classes can be purged by an injected CDN config
                         (the dashboard does this), inline style cannot. --}}
                    <span class="absolute -top-0.5 -right-0.5 min-w-[20px] h-[20px] px-1.5 inline-flex items-center justify-center text-[11px] font-bold leading-none rounded-full border-2"
                          style="background-color: #ef4444; color: #ffffff; border-color: #ffffff;">
                        {{ $topbarNotifications['unreadLabel'] }}
                    </span>
                    <span class="sr-only">{{ $topbarNotifications['unreadCount'] }} unread</span>
                @endif
            </div>
            <div class="notification-content pt-2 dropdown-menu">
                <div class="notification-content__box dropdown-content">
                    <div class="notification-content__title flex items-center justify-between">
                        <span>Notifications</span>
                        @if ($topbarNotifications['unreadCount'] > 0)
                            <form method="POST" action="{{ $topbarNotifications['markAllReadUrl'] }}" class="m-0">
                                @csrf
                                <button type="submit" class="text-xs text-primary hover:underline">Mark all read</button>
                            </form>
                        @endif
                    </div>

                    @forelse ($topbarNotifications['recent'] as $item)
                        <a href="{{ $item['followUrl'] }}" class="cursor-pointer relative flex items-start mt-3 first:mt-0 group">
                            <div class="w-10 h-10 flex-none flex items-center justify-center rounded-full bg-slate-100 dark:bg-darkmode-400 mr-2 relative">
                                {{-- Inline SVG per kind. Falls back to the bell silhouette for unknown kinds. --}}
                                @include('commons.menu.partials.notification-kind-icon', ['kind' => $item['kind'], 'isUnread' => $item['isUnread']])
                                @if ($item['isUnread'])
                                    <div class="w-2.5 h-2.5 bg-primary absolute right-0 top-0 rounded-full border-2 border-white"></div>
                                @endif
                            </div>
                            <div class="ml-1 overflow-hidden flex-1">
                                <div class="flex items-center">
                                    <span class="font-medium truncate mr-5 {{ $item['isUnread'] ? 'text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-300' }}">{{ $item['title'] }}</span>
                                    <div class="text-xs text-slate-400 ml-auto whitespace-nowrap">{{ $item['timeAgoLabel'] }}</div>
                                </div>
                                <div class="w-full truncate text-slate-500 text-xs mt-0.5">{{ $item['body'] }}</div>
                            </div>
                        </a>
                    @empty
                        <div class="text-center text-slate-400 text-sm py-6">You're all caught up.</div>
                    @endforelse

                    <div class="text-center mt-3 pt-3 border-t border-slate-200/60 dark:border-darkmode-400">
                        <a href="{{ $topbarNotifications['indexUrl'] }}" class="text-xs text-primary hover:underline">See all notifications</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- END: Notifications -->
        <!-- BEGIN: Account Menu -->
        <div class="intro-x dropdown w-8 h-8">
            <div class="dropdown-toggle w-8 h-8 rounded-full overflow-hidden shadow-lg image-fit zoom-in scale-110" role="button" aria-expanded="false" data-tw-toggle="dropdown">
                <img alt="User Photo" class="rounded-full"
                     src="{{ asset($user && $user->image_url ? 'uploads/users/' . $user->image_url : 'dist/images/profile-5.jpg') }}">
            </div>
            <div class="dropdown-menu w-56">
                <ul class="dropdown-content bg-primary/80 before:block before:absolute before:bg-black before:inset-0 before:rounded-md before:z-[-1] text-white">
                    <li class="p-2">
                        <div class="font-medium">{{ $user ? $user->full_name : 'Guest' }}</div>
                        <div class="text-xs text-white/60 mt-0.5 dark:text-slate-500">{{ $sector ? $sector->sector_name : '' }}</div>
                    </li>
{{--                    <li>--}}
{{--                        <hr class="dropdown-divider border-white/[0.08]">--}}
{{--                    </li>--}}
{{--                    <li>--}}
{{--                        <a href="" class="dropdown-item hover:bg-white/5"> <i data-lucide="user" class="w-4 h-4 mr-2"></i> Profile </a>--}}
{{--                    </li>--}}
{{--                    <li>--}}
{{--                        <a href="" class="dropdown-item hover:bg-white/5"> <i data-lucide="edit" class="w-4 h-4 mr-2"></i> Add Account </a>--}}
{{--                    </li>--}}
{{--                    <li>--}}
{{--                        <a href="" class="dropdown-item hover:bg-white/5"> <i data-lucide="lock" class="w-4 h-4 mr-2"></i> Reset Password </a>--}}
{{--                    </li>--}}
{{--                    <li>--}}
{{--                        <a href="" class="dropdown-item hover:bg-white/5"> <i data-lucide="help-circle" class="w-4 h-4 mr-2"></i> Help </a>--}}
{{--                    </li>--}}
{{--                    <li>--}}
{{--                        <hr class="dropdown-divider border-white/[0.08]">--}}
{{--                    </li>--}}
                    <li>
                        <a href="{{route("logout")}}" class="dropdown-item hover:bg-white/5"> <i data-lucide="toggle-right" class="w-4 h-4 mr-2"></i> Logout </a>
                    </li>
                </ul>
            </div>
        </div>
        <!-- END: Account Menu -->
    </div>
</div>
<!-- END: Top Bar -->
@endauth
