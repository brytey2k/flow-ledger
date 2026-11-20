<!doctype html>
<html class="h-full" lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title') — Flow Ledger user guide</title>
    <meta name="description" content="@yield('description')" />
    <link href="{{ asset('assets/media/app/favicon.ico') }}?v=3" rel="icon" type="image/x-icon" />
    <link href="{{ asset('assets/media/app/favicon-32x32.png') }}?v=3" rel="icon" sizes="32x32" type="image/png" />
    <link href="{{ asset('assets/media/app/apple-touch-icon.png') }}?v=3" rel="apple-touch-icon" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-foreground min-h-full text-base antialiased">
    @include('tenant.partials.theme-toggle')

    <a
        class="bg-primary text-primary-foreground sr-only z-50 rounded-[var(--radius)] px-3 py-2 text-sm focus:not-sr-only focus:fixed focus:start-4 focus:top-3"
        href="#docs-content"
    >Skip to content</a>

    <header class="border-border bg-background/90 sticky top-0 z-30 border-b backdrop-blur">
        <div class="mx-auto flex h-16 max-w-[1440px] items-center justify-between gap-4 px-4 lg:px-8">
            <a class="flex min-w-0 items-center gap-2 sm:gap-3" href="{{ route('landlord.docs') }}" aria-label="Flow Ledger user guide home">
                <img class="h-7 w-auto sm:h-8 dark:hidden" src="{{ asset('assets/media/app/flowledger_logo_light.png') }}" alt="Flow Ledger" />
                <img class="hidden h-7 w-auto sm:h-8 dark:block" src="{{ asset('assets/media/app/flowledger_logo_dark.png') }}" alt="Flow Ledger" />
                <span class="sgh-badge sgh-badge-accent whitespace-nowrap">User guide</span>
            </a>
            <div class="flex items-center gap-2">
                @if ($guide->videos()->isNotEmpty())
                    <a
                        class="sgh-btn sgh-btn-ghost hidden sm:inline-flex"
                        href="{{ route('landlord.docs.videos') }}"
                        @if (request()->routeIs('landlord.docs.videos')) aria-current="page" @endif
                    >
                        <x-tabler-movie class="size-4" aria-hidden="true" /> Videos
                    </a>
                @endif
                <div x-data="themeToggle">
                    <button
                        type="button"
                        class="sgh-btn sgh-btn-ghost sgh-btn-icon"
                        @click="toggleTheme"
                        :aria-pressed="dark.toString()"
                        aria-label="Dark mode"
                    >
                        <x-tabler-moon-filled class="size-4" x-show="! dark" aria-hidden="true" />
                        <x-tabler-sun-filled class="size-4" x-show="dark" x-cloak aria-hidden="true" />
                    </button>
                </div>
                <a class="sgh-btn sgh-btn-outline hidden sm:inline-flex" href="{{ url('/') }}">Flow Ledger home</a>
            </div>
        </div>
    </header>

    @yield('content')

    <footer class="border-border border-t">
        <div class="text-muted-foreground mx-auto flex max-w-[1440px] flex-wrap items-center justify-between gap-3 px-4 py-6 text-sm lg:px-8">
            <span>&copy; {{ date('Y') }} Flow Ledger</span>
            <a class="sgh-link" href="{{ route('landlord.docs', 'troubleshooting') }}#support">Contact support</a>
        </div>
    </footer>
</body>
</html>
