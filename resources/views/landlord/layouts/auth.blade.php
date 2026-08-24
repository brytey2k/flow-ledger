<!DOCTYPE html>
<html class="h-full" dir="ltr" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('landlord.layouts.partials.head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="antialiased h-full text-base text-foreground bg-background">
@include('tenant.partials.theme-toggle')

<div class="grid min-h-full lg:grid-cols-[1fr_1.05fr]">
    <div class="hidden flex-col bg-auth-panel-background p-12 text-auth-panel-foreground lg:flex">
        <div class="flex items-center">
            <img class="h-6 w-auto" src="{{ asset('assets/media/app/flowledger_logo_dark.png') }}" alt="{{ config('app.name', 'Flow Ledger') }}" />
        </div>
        <div class="mt-auto">
            <p class="mb-6 max-w-[26ch] text-2xl leading-snug tracking-tight text-balance">{{ __('auth.pitch') }}</p>
            <div class="grid gap-3.5 text-sm text-auth-panel-muted">
                @foreach (__('auth.pitch_points') as $point)
                    <div class="flex items-baseline gap-2.5">
                        <span class="inline-block size-[5px] shrink-0 rounded-full bg-primary"></span>
                        {{ $point }}
                    </div>
                @endforeach
            </div>
        </div>
        <div class="mt-11 border-t border-auth-panel-border pt-5 font-mono text-[11px] text-auth-panel-caption">
            {{ __('auth.secured_by') }}
        </div>
    </div>
    <div class="flex flex-col items-center justify-center p-6 lg:p-12">
        <div class="mb-8 flex items-center justify-center lg:hidden">
            <img class="h-6 w-auto dark:hidden" src="{{ asset('assets/media/app/flowledger_logo_light.png') }}" alt="{{ config('app.name', 'Flow Ledger') }}" />
            <img class="hidden h-6 w-auto dark:block" src="{{ asset('assets/media/app/flowledger_logo_dark.png') }}" alt="{{ config('app.name', 'Flow Ledger') }}" />
        </div>
        <div class="w-full max-w-[404px]">
            @yield('content')
        </div>
    </div>
</div>

@stack('scripts')
</body>
</html>
