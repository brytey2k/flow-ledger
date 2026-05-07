<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="en">
<head>
    @include('landlord.layouts.partials.head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="demo1 kt-sidebar-fixed kt-header-fixed flex h-full bg-background text-base text-foreground antialiased">
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

@include('landlord.layouts.partials.scripts')
</body>
</html>
