<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="en">
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
    <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet"/>
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet"/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="demo1 kt-sidebar-fixed kt-header-fixed flex h-full bg-background text-base text-foreground antialiased">
    <!-- Theme Mode -->
    <script>
        const defaultThemeMode = 'light'; // light|dark|system
        let themeMode;

        if (document.documentElement) {
            if (localStorage.getItem('kt-theme')) {
                themeMode = localStorage.getItem('kt-theme');
            } else if (
                document.documentElement.hasAttribute('data-kt-theme-mode')
            ) {
                themeMode =
                    document.documentElement.getAttribute('data-kt-theme-mode');
            } else {
                themeMode = defaultThemeMode;
            }

            if (themeMode === 'system') {
                themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches
                    ? 'dark'
                    : 'light';
            }

            document.documentElement.classList.add(themeMode);
        }
    </script>
    <!-- End of Theme Mode -->

    <!-- Page -->
    <!-- Main -->
    <div class="flex grow">
        @include('landlord.layouts.sidebar')

        <!-- Wrapper -->
        <div class="kt-wrapper flex grow flex-col">
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

    <!-- Scripts -->
    <script src="{{ asset('assets/js/core.bundle.js') }}"></script>
    <script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>
    <!-- End of Scripts -->

    @stack('scripts')
</body>
</html>
