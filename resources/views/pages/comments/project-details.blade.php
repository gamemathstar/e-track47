@php use Carbon\Carbon; @endphp
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
    <title>
        Projects
    </title>
    <!-- BEGIN: CSS Assets-->
    <link rel="stylesheet" href="{{ asset('dist/css/app.css') }}"/>
    <!-- END: CSS Assets-->
</head>
<!-- END: Head -->
<body class="py-5 md:py-0">
<!-- BEGIN: Mobile Menu -->
<div class="mobile-menu md:hidden">
    <div class="mobile-menu-bar">
        <a href="" class="flex mr-auto">
            <img alt="Midone - HTML Admin Template" class="w-6" src="{{ asset('jg_logo.png') }}">
        </a>
        <a href="javascript:;" class="mobile-menu-toggler"> <i data-lucide="bar-chart-2"
                                                               class="w-8 h-8 text-white transform -rotate-90"></i> </a>
    </div>
    <div class="scrollable">
        <a href="javascript:;" class="mobile-menu-toggler"> <i data-lucide="x-circle"
                                                               class="w-8 h-8 text-white transform -rotate-90"></i> </a>
        <ul class="scrollable__content py-2">
            <li>
                <a href="{{ route('home') }}" class="menu">
                    <div class="menu__icon"><i data-lucide="activity"></i></div>
                    <div class="menu__title"> Projects</div>
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
            <span class="logo__text text-white text-lg ml-3"> JS-EPM </span>
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
                <div class="top-menu__icon"><i data-lucide="activity"></i></div>
                <div class="top-menu__title"> Projects</div>
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
    <div class="intro-y grid grid-cols-12 gap-6 mt-8">
        <div class="intro-y news col-span-12 md:col-span-6 xl:col-span-6 box p-5">
            <!-- BEGIN: Blog Layout -->
            <h2 class="intro-y font-medium text-xl sm:text-2xl">
                {{ $commitment->name }}
            </h2>
            <div
                class="intro-y text-slate-600 dark:text-slate-500 mt-3 text-xs sm:text-sm">
                {{ Carbon::parse($commitment->start_date)->format('jS F Y') }}
                <span class="mx-1"> - </span> {{  Carbon::parse($commitment->end_date)->format('jS F Y')  }}
            </div>
            <div class="intro-y mt-6">
                <div class="news__preview image-fit">
                    <img alt="Midone - HTML Admin Template" class="rounded-md"
                         src="{{ asset('uploads/'.$commitment->img_url) }}">
                </div>
            </div>
            <div class="text-slate-600 dark:text-slate-500 mt-5">
                {{ $commitment->description }}
            </div>
            <div class="intro-y flex relative pt-16 sm:pt-6 items-center pb-6">
                <div
                    class="absolute sm:relative -mt-12 sm:mt-0 w-full flex text-slate-600 dark:text-slate-500 text-xs sm:text-sm">
                    <div class="intro-x mr-1 sm:mr-3"> Comments: <span
                            class="font-medium">{{ $commitment->commentsCount() }}</span></div>
                </div>
            </div>
            <!-- END: Blog Layout -->
            <!-- BEGIN: Comments -->
            <div class="intro-yx pt-5 mb-5 border-t border-slate-200/60 dark:border-darkmode-400">
                <div class="text-base sm:text-lg font-medium">Leave a comment</div>
                <form action="{{ route('home.post.comment') }}" method="post">
                    @csrf
                    <input type="hidden" name="commitment_id" value="{{ $commitment->id }}">
                    <input type="text" name="commenter_name"
                           class="form-control border-transparent bg-slate-100 pr-10 mt-2"
                           placeholder="Your Name" required>
                    <div class="news__input relative mt-3">
                        <i data-lucide="message-circle"
                           class="w-5 h-5 absolute my-auto inset-y-0 ml-6 left-0 text-slate-500"></i>
                        <textarea class="form-control border-transparent bg-slate-100 pl-16 py-6 resize-none" rows="1"
                                  placeholder="Your comment" name="comment" required></textarea>

                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Post</button>
                </form>
            </div>
            <div class="intro-y mt-5 pb-10">
                @php $comments = $commitment->allComments(); @endphp
                @if(count($comments) > 0)
                    @foreach($comments as $comment)
                        <div
                            class="pt-5 {{ $loop->iteration>1?'mt-5 border-t border-slate-200/60 dark:border-darkmode-400':'' }}">
                            <div class="flex">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 flex-none image-fit">
                                    <img alt="Midone - HTML Admin Template" class="rounded-full"
                                         src="{{ asset('dist/images/profile-7.jpg') }}">
                                </div>
                                <div class="ml-3 flex-1">
                                    <div class="flex items-center">
                                        <span class="font-medium"> {{ $comment->commenter_name }} </span>
                                    </div>
                                    <div
                                        class="text-slate-500 text-xs sm:text-sm">{{  Carbon::parse($comment->created_at)->format('jS F Y')  }}</div>
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
            <!-- END: Comments -->
        </div>
        <div class="intro-y news col-span-12 md:col-span-6 xl:col-span-6">
            @php $deliverables = $commitment->deliverables; @endphp
            <div class="intro-y grid grid-cols-12 gap-6">
                <div class="intro-y news col-span-12 md:col-span-12 xl:col-span-12 p-5">
                    <div class="intro-y flex flex-col sm:flex-row items-center">
                        <h2 class="text-lg font-medium mr-auto">Deliverables</h2>
                    </div>

                    @if($deliverables->count())
                        <div class="overflow-x-auto">
                            <table class="table table-report mt-2">
                                <thead>
                                <tr>
                                    <th class="whitespace-nowrap">#</th>
                                    <th class="whitespace-nowrap">Deliverable</th>
                                    {{--                                    <th class="whitespace-nowrap">Budget</th>--}}
                                    <th class="whitespace-nowrap">Start Date</th>
                                    <th class="whitespace-nowrap">Status</th>
                                    <th class="whitespace-nowrap">Progress</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($deliverables as $deliverable)
                                    <tr>
                                        <td>
                                            {{ $loop->iteration }}
                                        </td>
                                        <td>{{ $deliverable->deliverable }}</td>
                                        {{--                                        <td>&#8358;{{ number_format($deliverable-> budget)}}</td>--}}
                                        <td>{{ $deliverable->start_date }}</td>
                                        <td>{{ $deliverable->status }}</td>
                                        <td>
                                            @if($deliverable->status != 'Not Started')
                                                {{ $deliverable->progress() }}
                                            @else
                                                - - -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <center>
                            Deliverable(s) not added yet.
                        </center>
                    @endif
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
