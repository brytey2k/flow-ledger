<!-- Header -->
<header class="sgh-header fixed end-0 top-0 z-10 flex shrink-0 items-stretch bg-background border-b border-border" id="header">
    <!-- Container -->
    <div class="sgh-container-fixed flex items-stretch justify-between lg:gap-4" id="headerContainer">
        <!-- Mobile Logo -->
        <div class="-ms-1 flex items-center gap-2.5 lg:hidden">
            <a class="shrink-0 dark:hidden" href="{{ route('landlord.tenants.index') }}">
                <img class="max-h-[25px] w-full" src="{{ asset('assets/media/app/flowledger_icon_light.png') }}"/>
            </a>
            <a class="hidden shrink-0 dark:block" href="{{ route('landlord.tenants.index') }}">
                <img class="max-h-[25px] w-full" src="{{ asset('assets/media/app/flowledger_icon_dark.png') }}"/>
            </a>
            <div class="flex items-center">
                <button class="sgh-btn sgh-btn-icon sgh-btn-ghost" @click="$store.sidebarDrawer.show()">
                    <x-tabler-menu />
                </button>
            </div>
        </div>
        <!-- End of Mobile Logo -->
        <div class="flex items-stretch gap-4 lg:gap-7.5">
            <!-- App / Area Title -->
            <div class="hidden items-center gap-2.5 lg:flex">
                <span class="text-lg font-semibold leading-none tracking-tight text-foreground">{{ config('app.name', 'FlowLedger') }}</span>
                <span class="sgh-badge sgh-badge-accent font-mono uppercase tracking-wider">{{ __('navigation.landlord_kind') }}</span>
            </div>
            <!-- End of App / Area Title -->
        </div>
        <!-- Topbar -->
        <div class="flex items-center gap-2.5">
            @include('landlord.partials.topbar-user-dropdown')
        </div>
        <!-- End of Topbar -->
    </div>
    <!-- End of Container -->
</header>
<!-- End of Header -->
