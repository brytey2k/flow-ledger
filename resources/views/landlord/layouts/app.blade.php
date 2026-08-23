<!DOCTYPE html>
<html class="h-full" dir="ltr" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'Clean Team Ghana') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="{{ asset('assets/media/app/apple-touch-icon.png') }}" rel="apple-touch-icon" sizes="180x180"/>
    <link href="{{ asset('assets/media/app/favicon-32x32.png') }}" rel="icon" sizes="32x32" type="image/png"/>
    <link href="{{ asset('assets/media/app/favicon-16x16.png') }}" rel="icon" sizes="16x16" type="image/png"/>
    <link href="{{ asset('assets/media/app/favicon.ico') }}" rel="shortcut icon"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="{{ asset('assets/vendors/apexcharts/apexcharts.css') }}" rel="stylesheet"/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="flex h-full bg-background text-base text-foreground antialiased">
    <!-- Theme Mode -->
    <script>
        (function () {
            let themeMode = localStorage.getItem('sgh-theme') || 'light';
            if (themeMode === 'system') {
                themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.classList.add(themeMode);
        })();
    </script>
    <!-- End of Theme Mode -->

    <!-- Page -->
    <!-- Main -->
    <div class="sgh-shell" x-data="sidebarCollapse" :class="{ 'is-collapsed': collapsed }">
        @include('landlord.layouts.sidebar')

        <!-- Wrapper -->
        <div class="sgh-wrapper flex grow flex-col">
            @include('landlord.layouts.header')

            <!-- Content -->
            <main class="grow pt-5" id="content" role="content">
                @include('landlord.layouts.flash')
                @yield('content')
            </main>
            <!-- End of Content -->

            @include('landlord.layouts.footer')
        </div>
        <!-- End of Wrapper -->
    </div>
    <!-- End of Main -->
    <!-- End of Page -->

    @stack('scripts')
</body>
</html>
