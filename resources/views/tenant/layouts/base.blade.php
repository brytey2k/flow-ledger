<!DOCTYPE html>
<html class="h-full" dir="ltr" lang="en">
<head>
    @include('tenant.layouts.partials.head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased flex h-full text-base text-foreground bg-background">
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
    @include('tenant.layouts.sidebar')

    <!-- Wrapper -->
    <div class="sgh-wrapper flex grow flex-col">
        @include('tenant.layouts.header')

        <!-- Content -->
        <main class="grow pt-5" id="content" role="content">
            @if(session('impersonated') === true)
                @php /** @var \App\Models\Tenant\User|null $impersonatedUser */ $impersonatedUser = auth('web')->user(); @endphp
                <div class="sgh-container-fixed pb-4">
                    <div class="sgh-alert sgh-alert-warning flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <x-tabler-user-square-rounded />
                            <span class="font-medium">Impersonation mode: You are viewing this tenant as {{ $impersonatedUser?->first_name }} {{ $impersonatedUser?->last_name }}.</span>
                        </div>
                        <form action="{{ route('exit-impersonation') }}" method="POST">
                            @csrf
                            <button type="submit" class="sgh-btn sgh-btn-sm sgh-btn-outline">Exit Impersonation</button>
                        </form>
                    </div>
                </div>
            @endif
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
