<!-- Sidebar -->
<div x-show="$store.sidebarDrawer.open" x-cloak class="fixed inset-0 z-10 bg-foreground/40 lg:hidden" @click="$store.sidebarDrawer.hide()"></div>
<div class="sgh-sidebar fixed bottom-0 top-0 z-20 hidden shrink-0 flex-col items-stretch border-e border-e-border bg-background lg:flex"
    :class="{ 'flex': $store.sidebarDrawer.open }" @click.outside="$store.sidebarDrawer.hide()" id="sidebar">
    <div class="relative hidden shrink-0 items-center justify-between px-3 lg:flex lg:px-6"
        id="sidebar_header">
        <a class="dark:hidden" href="{{ route('landlord.tenants.index') }}">
            <img class="default-logo min-h-[22px] max-w-none" src="{{ asset('assets/media/app/flowledger_logo_light.png') }}" />
            <img class="small-logo min-h-[22px] max-w-none" src="{{ asset('assets/media/app/flowledger_icon_light.png') }}" />
        </a>
        <a class="hidden dark:block" href="{{ route('landlord.tenants.index') }}">
            <img class="default-logo min-h-[22px] max-w-none" src="{{ asset('assets/media/app/flowledger_logo_dark.png') }}" />
            <img class="small-logo min-h-[22px] max-w-none" src="{{ asset('assets/media/app/flowledger_icon_dark.png') }}" />
        </a>
        <button
            @click="toggle" :aria-expanded="!collapsed"
            class="sgh-btn sgh-btn-outline sgh-btn-icon absolute start-full top-2/4 size-[30px] -translate-x-2/4 -translate-y-2/4 rtl:translate-x-2/4 hidden lg:inline-flex"
            id="sidebar_toggle">
            <span :class="{ 'rotate-180 rtl:rotate-0': collapsed }" class="inline-flex transition-transform duration-300">
                <x-tabler-chevron-left class="rtl:rotate-180" />
            </span>
        </button>
    </div>
    <div class="flex min-h-0 shrink-0 grow py-5 pe-2" id="sidebar_content">
        <div class="flex min-h-0 shrink-0 grow overflow-y-auto pe-1 ps-2 lg:pe-3 lg:ps-5" id="sidebar_scrollable">
            <!-- Sidebar Menu -->
            <div class="flex grow flex-col gap-1" id="sidebar_menu">
                <div class="sgh-nav-heading">
                    System Management
                </div>

                {{-- Tenants --}}
                <a href="{{ route('landlord.tenants.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('landlord.tenants.*') ? 'active' : '' }}">
                    <span class="sgh-nav-icon">
                        <x-tabler-briefcase-filled class="text-lg" />
                    </span>
                    <span class="sgh-nav-title">Tenants</span>
                </a>

                {{-- Feature Flags --}}
                <a href="{{ route('landlord.feature-flags.index') }}" class="sgh-nav-item gap-2.5 {{ request()->routeIs('landlord.feature-flags.*') ? 'active' : '' }}">
                    <span class="sgh-nav-icon">
                        <x-tabler-toggle-right-filled class="text-lg" />
                    </span>
                    <span class="sgh-nav-title">Feature Flags</span>
                </a>
            </div>
            <!-- End of Sidebar Menu -->
        </div>
    </div>
</div>
<!-- End of Sidebar -->
