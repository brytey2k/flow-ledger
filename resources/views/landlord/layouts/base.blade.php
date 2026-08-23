<!DOCTYPE html>
<html class="h-full" dir="ltr" lang="en">
<head>
    @include('landlord.layouts.partials.head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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

@include('landlord.layouts.partials.scripts')
</body>
</html>
