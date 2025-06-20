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
    <title>Login - Performance Delivery Coordination Unit (PDCU</title>
    <!-- BEGIN: CSS Assets-->
    <link rel="stylesheet" href="/dist/css/app.css"/>
    <!-- END: CSS Assets-->
</head>
<!-- END: Head -->
<body class="login">
<div class="container sm:px-10">
    <div class="block xl:grid grid-cols-2 gap-4">
        <!-- BEGIN: Login Info -->
        <div class="hidden xl:flex flex-col min-h-screen">
            <a href="" class="-intro-x flex items-center pt-5">
                <img alt="Midone - HTML Admin Template" class="w-6" src="{{asset('jg_logo.png')}}">
                <span class="text-white text-lg ml-3"> Performance Delivery Coordination Unit (PDCU </span>
            </a>
            <div class="my-auto">
                <img alt="Midone - HTML Admin Template" class="-intro-x w-1/2 -mt-16"
                     src="{{asset('jigawa3_map.png')}}">

            </div>
        </div>
        <!-- END: Login Info -->
        <!-- BEGIN: Login Form -->
        <div class="h-screen xl:h-auto flex py-5 xl:py-0 my-10 xl:my-0">
            <div
                class="my-auto mx-auto xl:ml-20 bg-white dark:bg-darkmode-600 xl:bg-transparent px-5 sm:px-8 py-8 xl:p-0 rounded-md shadow-md xl:shadow-none w-full sm:w-3/4 lg:w-2/4 xl:w-auto">
                <h2 class="intro-x font-bold text-2xl xl:text-3xl text-center xl:text-left">
                    Sign In
                </h2>
                <div class="intro-x mt-2 text-slate-400  text-center">
                </div>
                <form method="POST" action="{{ route('login.process') }}">
                    <div class="intro-x mt-8">
                        @csrf
                        <input type="email" name="email" class="intro-x login__input form-control py-3 px-4 block"
                               placeholder="Email" value="{{ old('email') }}" required autofocus>
                        @error('email')
                        <span>{{ $message }}</span>
                        @enderror
                        <input type="password" class="intro-x login__input form-control py-3 px-4 block mt-4"
                               placeholder="Password" name="password" required>
                        @error('password')
                        <span>{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="intro-x flex text-slate-600 dark:text-slate-500 text-xs sm:text-sm mt-4">
                        <div class="flex items-center mr-auto">
                            <input type="checkbox" class="form-check-input border mr-2"
                                   id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="cursor-pointer select-none" for="remember-me">Remember me</label>
                        </div>
                        <a href="">Forgot Password?</a>
                    </div>
                    <div class="intro-x mt-5 xl:mt-8 text-center xl:text-left">
                        <button class="btn btn-primary py-3 px-4 w-full xl:w-32 xl:mr-3 align-top">Login</button>
                        {{--                        <button class="btn btn-outline-secondary py-3 px-4 w-full xl:w-32 mt-3 xl:mt-0 align-top">Register</button>--}}
                    </div>
                    <div class="intro-x mt-10 xl:mt-24 text-slate-600 dark:text-slate-500 text-center xl:text-left"> By
                        signin up, you agree to our <a class="text-primary dark:text-slate-200" href="">Terms and
                            Conditions</a> & <a class="text-primary dark:text-slate-200" href="">Privacy Policy</a>
                    </div>
                </form>
            </div>
        </div>
        <!-- END: Login Form -->
    </div>
</div>
<!-- BEGIN: JS Assets-->
<script src="dist/js/app.js"></script>
<!-- END: JS Assets-->
</body>
</html>
