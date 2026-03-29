<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <link href="{{asset('jg_logo.png')}}" rel="shortcut icon">
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Login - PDCU Management System | Jigawa State</title>
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
<div class="min-h-screen flex">
    <!-- Left Side - Branding & Image -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary to-primary/80 relative overflow-hidden">
        <div
            class="absolute inset-0 bg-gradient-to-r from-background-dark/90 via-background-dark/60 to-transparent z-10"></div>
        <img alt="Jigawa State" class="absolute inset-0 w-full h-full object-cover opacity-30"
             src="{{asset('jigawa3_map.png')}}">
        <div class="relative z-20 flex flex-col justify-between p-12 text-white">
            <div>
                <a href="{{ route('home') }}" class="flex items-center gap-3 mb-8">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white/20 backdrop-blur-sm">
                        <img alt="Jigawa State Crest" class="h-10 w-10 object-contain"
                             src="{{asset('jg_logo.png')}}">
                    </div>
                    <div class="flex flex-col">
                        <h1 class="text-xl font-bold tracking-tight leading-none">PDCU</h1>
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-white/90">Performance
                            Delivery Coordination Unit</p>
                    </div>
                </a>
            </div>
            <div class="max-w-md">
                <div
                    class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/20 px-4 py-1.5 backdrop-blur-sm">
                    <span class="h-2 w-2 rounded-full bg-white"></span>
                    <span class="text-xs font-bold uppercase tracking-wider">Secure Access</span>
                </div>
                <h2 class="text-4xl font-extrabold leading-tight mb-4">
                    Welcome to <span class="text-white">PDCU</span> Portal
                </h2>
                <p class="text-lg text-white/90 leading-relaxed">
                    Access your dashboard to manage performance metrics, submit reports, and track departmental KPIs for
                    Jigawa State.
                </p>
            </div>
            <div class="flex items-center gap-2 text-sm text-white/80">
                <span class="material-icons text-sm">security</span>
                <span>Secure & Encrypted</span>
            </div>
        </div>
    </div>

    <!-- Right Side - Login Form -->
    <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
        <div class="w-full max-w-md">
            <!-- Mobile Logo -->
            <div class="lg:hidden mb-8 text-center">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10">
                        <img alt="Jigawa State Crest" class="h-10 w-10 object-contain"
                             src="{{asset('jg_logo.png')}}">
                    </div>
                    <div class="flex flex-col">
                        <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white leading-none">
                            PDCU</h1>
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-primary">Performance Delivery
                            Coordination Unit</p>
                    </div>
                </a>
            </div>

            <!-- Login Form Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-8 lg:p-10">
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Sign In</h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400">Enter your credentials to access your
                        account</p>
                </div>

                <form method="POST" action="{{ route('login.process') }}">
                    @csrf

                    <!-- Email Field -->
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Email Address
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                <span class="material-icons text-xl">email</span>
                            </span>
                            <input type="email" id="email" name="email"
                                   class="w-full pl-12 pr-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-700 dark:text-white transition-all"
                                   placeholder="Enter your email" value="{{ old('email') }}" required autofocus>
                        </div>
                        @error('email')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Field -->
                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                <span class="material-icons text-xl">lock</span>
                            </span>
                            <input type="password" id="password" name="password"
                                   class="w-full pl-12 pr-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-700 dark:text-white transition-all"
                                   placeholder="Enter your password" required>
                        </div>
                        @error('password')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <input type="checkbox" id="remember" name="remember"
                                   class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary dark:bg-slate-700 dark:border-slate-600"
                                {{ old('remember') ? 'checked' : '' }}>
                            <label for="remember"
                                   class="ml-2 text-sm text-slate-600 dark:text-slate-400 cursor-pointer">
                                Remember me
                            </label>
                        </div>
                        {{--                        <a href="#" class="text-sm font-medium text-primary hover:text-primary/80 transition-colors">--}}
                        {{--                            Forgot Password?--}}
                        {{--                        </a>--}}
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                            class="w-full rounded-lg bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                        <span class="flex items-center justify-center gap-2">
                            <span>Sign In</span>
                            <span class="material-icons text-sm">arrow_forward</span>
                        </span>
                    </button>
                </form>

                <!-- Footer Links -->
                {{--                <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700">--}}
                {{--                    <p class="text-xs text-center text-slate-500 dark:text-slate-400">--}}
                {{--                        By signing in, you agree to our--}}
                {{--                        <a href="#" class="text-primary hover:text-primary/80 transition-colors">Terms and--}}
                {{--                            Conditions</a>--}}
                {{--                        & <a href="#" class="text-primary hover:text-primary/80 transition-colors">Privacy Policy</a>--}}
                {{--                    </p>--}}
                {{--                </div>--}}

                <!-- Back to Home -->
                <div class="mt-6 text-center">
                    <a href="{{ route('home') }}"
                       class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400 hover:text-primary transition-colors">
                        <span class="material-icons text-sm">arrow_back</span>
                        <span>Back to Home</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
