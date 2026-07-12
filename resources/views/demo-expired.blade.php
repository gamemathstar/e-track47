<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <link href="{{asset('jg_logo.png')}}" rel="shortcut icon">
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Demo Period Expired | PDCU Jigawa</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
    <style>
        body {
            font-family: 'Public Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 antialiased font-display min-h-screen flex flex-col">
<header class="w-full border-b border-primary/10 bg-white/80 dark:bg-background-dark/80 backdrop-blur-md">
    <div class="container mx-auto flex h-20 items-center px-6 lg:px-12">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10">
                <img alt="Jigawa State Crest" class="h-10 w-10 object-contain" src="{{asset('jg_logo.png')}}">
            </div>
            <div class="flex flex-col">
                <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white leading-none">PDCU</h1>
                <p class="text-[10px] font-semibold uppercase tracking-widest text-primary">Performance Delivery
                    Coordination Unit</p>
            </div>
        </div>
    </div>
</header>
<main class="flex-1 flex items-center justify-center px-6 py-24">
    <div class="max-w-xl w-full text-center">
        <div class="mx-auto mb-8 flex h-20 w-20 items-center justify-center rounded-full bg-primary/10">
            <span class="material-icons text-4xl text-primary">hourglass_disabled</span>
        </div>
        <h2 class="text-3xl font-bold text-slate-900 dark:text-white lg:text-4xl">
            Demo Period Has Ended
        </h2>
        <p class="mt-6 text-lg text-slate-600 dark:text-slate-400 leading-relaxed">
            Thank you for trying out the PDCU Performance Tracking System. The evaluation period for this demo
            has now expired and the site is no longer accessible in this mode.
        </p>
        <p class="mt-4 text-base text-slate-600 dark:text-slate-400 leading-relaxed">
            Please contact the system administrator to renew access or discuss deployment options.
        </p>
        <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
            <a href="mailto:info@pdcu.jg.gov.ng"
               class="flex items-center gap-2 rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all">
                <span class="material-icons text-sm">mail</span>
                Contact Administrator
            </a>
        </div>
    </div>
</main>
<footer class="border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark py-8">
    <div class="container mx-auto px-6 lg:px-12 text-center">
        <p class="text-xs text-slate-500 dark:text-slate-500">
            © {{ date('Y') }} Jigawa State PDCU. All rights reserved.
        </p>
    </div>
</footer>
</body>
</html>
