<html lang="en" class="light">
<!-- BEGIN: Head -->
<head>
    <meta charset="utf-8">
    <link href="/dist/images/logo.svg" rel="shortcut icon">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Enigma admin is super flexible, powerful, clean & modern responsive tailwind admin template with unlimited possibilities.">
    <meta name="keywords" content="admin template, Enigma Admin Template, dashboard template, flat admin template, responsive admin template, web app">
    <meta name="author" content="LEFT4CODE">
    <title>Jigawa State Government e-Track247</title>
    <!-- BEGIN: CSS Assets-->
    <link rel="stylesheet" href="/dist/css/app.css" />
    <!-- END: CSS Assets-->
</head>
<!-- END: Head -->
<body class="py-5 md:py-0">
@include('commons.menu.mobile')
@include('commons.menu.topbar')

<div class="flex overflow-hidden">
    @include('commons.menu.sidebar')
    <!-- BEGIN: Content -->
    <div class="content">

        @yield('content')
    </div>
</div>
<script src="/dist/js/app.js"></script>
@yield('js')
</body>
</html>
