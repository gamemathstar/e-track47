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
    <title>Jigawa State Government e-Track247</title>
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
            <img alt="Jigawa State Government e-Track247" class="w-6" src="{{asset('jg_logo.png')}}">
        </a>
        <a href="javascript:;" class="mobile-menu-toggler">
            <i data-lucide="bar-chart-2"  class="w-8 h-8 text-white transform -rotate-90"></i>
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
            <span class="logo__text text-white text-lg ml-3"> Jigawa State Government e-Track247 </span>
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
        <h2 class="text-lg font-medium mr-auto">Projects</h2>
    </div>
    <div class="intro-y grid grid-cols-12 gap-6 mt-5">
        <!-- BEGIN: Blog Layout -->
        @foreach($commitments as $commitment)
            @if($commitment->deliverables->count())
                <div class="intro-y col-span-12 md:col-span-6 xl:col-span-4 box">
                    <a href="{{ route('public.project.details',[$commitment->id]) }}">
                        <div class="p-5">
                            <div class="h-40 2xl:h-56 image-fit">
                                <img alt="Midone - HTML Admin Template" class="rounded-md"
                                     src="{{$commitment->img_url?asset('uploads/'.$commitment->img_url):"dist/images/preview-3.jpg"}}">
                            </div>
                            <span class="block font-medium text-base mt-5"> {{ $commitment->name }} </span>
                            <div class="text-slate-600 dark:text-slate-500 mt-2">
                                {{ $commitment->description }}
                            </div>
                        </div>
                    </a>
                    <div class="px-5 pt-3 pb-5 border-t border-slate-200/60 dark:border-darkmode-400">
                        <div class="w-full flex text-slate-500 text-xs sm:text-sm">
                            <div class="mr-2">
                                Comments: <span class="font-medium">{{ $commitment->commentsCount() }}</span>
                            </div>
                        </div>
                        {{--                    <div class="w-full flex items-center mt-3">--}}
                        {{--                        <div class="flex-1 relative text-slate-600">--}}
                        {{--                            <form action="{{ route('home.post.comment') }}" method="post">--}}
                        {{--                                @csrf--}}
                        {{--                                <input type="hidden" name="commitment_id" value="{{ $commitment->id }}">--}}
                        {{--                                <input type="text" name="commenter_name"--}}
                        {{--                                       class="form-control border-transparent bg-slate-100 pr-10"--}}
                        {{--                                       placeholder="Your Name" required>--}}
                        {{--                                <textarea name="comment" cols="30" rows="5" placeholder="Your comment" required--}}
                        {{--                                          class="form-control border-transparent bg-slate-100 pr-10 mt-2"></textarea>--}}

                        {{--                                <input type="submit" class="btn btn-primary mt-3 float-right" value="Post">--}}
                        {{--                            </form>--}}
                        {{--                        </div>--}}
                        {{--                    </div>--}}
                    </div>
                    <div class="px-5 pt-3 pb-5 border-t border-slate-200/60 dark:border-darkmode-400">
                        <div class="w-full flex text-slate-500 text-xs sm:text-sm">
                            <div class="mr-2">
                                Recent Comments
                            </div>
                        </div>
                        @php $comments = $commitment->recentComments(); @endphp
                        @if(count($comments) > 0)
                            @foreach($comments as $comment)
                                <div
                                    class="pt-5 {{ $loop->iteration>1?'mt-5 border-t border-slate-200/60 dark:border-darkmode-400':'' }}">
                                    <div class="flex">
                                        <div class="w-10 h-10 sm:w-12 sm:h-12 flex-none image-fit">
                                            <img alt="Midone - HTML Admin Template" class="rounded-full"
                                                 src="dist/images/profile-7.jpg">
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <div class="flex items-center">
                                                <span class="font-medium">
                                                    {{ $comment->commenter_name }}
                                                </span>
                                            </div>
                                            <div class="text-slate-500 text-xs sm:text-sm">{{  \Carbon\Carbon::parse($comment->created_at)->format('jS F Y')  }}</div>
                                            <div class="mt-2">{{ $comment->comment }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div>
                                <center>
                                    <p class="text-lg text-gray-600 mt-5">No comment posted</p>
                                </center>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
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
