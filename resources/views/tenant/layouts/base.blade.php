<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="en">
<head>
    @include('tenant.layouts.partials.head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased flex h-full text-base text-foreground bg-background demo1 kt-sidebar-fixed kt-header-fixed">
<!-- Theme Mode -->
<script>
    const defaultThemeMode = 'light'; // light|dark|system
    let themeMode;
    if (document.documentElement) {
        if (localStorage.getItem('kt-theme')) {
            themeMode = localStorage.getItem('kt-theme');
        } else if (document.documentElement.hasAttribute('data-kt-theme-mode')) {
            themeMode = document.documentElement.getAttribute('data-kt-theme-mode');
        } else {
            themeMode = defaultThemeMode;
        }
        if (themeMode === 'system') {
            themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        document.documentElement.classList.add(themeMode);
    }
</script>
<!-- End of Theme Mode -->
<!-- Page -->
<!-- Main -->
<div class="flex grow">
    @include('tenant.layouts.sidebar')

    <!-- Wrapper -->
    <div class="kt-wrapper flex grow flex-col">
        @include('tenant.layouts.header')

        <!-- Content -->
        <main class="grow pt-5" id="content" role="content">
            @include('tenant.layouts.flash')
            @yield('content')
        </main>
        <!-- End of Content -->

        @include('tenant.layouts.footer')
    </div>
    <!-- End of Wrapper -->
</div>
<!-- End of Main -->
<!-- End of Page -->

@include('tenant.layouts.partials.scripts')
</body>
</html>
