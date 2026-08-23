<!-- Header -->
<header class="kt-header fixed end-0 start-0 top-0 z-10 flex shrink-0 items-stretch bg-background" data-kt-sticky="true"
        data-kt-sticky-class="border-b border-border" data-kt-sticky-name="header" id="header">
    <!-- Container -->
    <div class="fl-container-fixed flex items-stretch justify-between lg:gap-4" id="headerContainer">
        <!-- Mobile Logo -->
        <div class="-ms-1 flex items-center gap-2.5 lg:hidden">
            <a class="shrink-0" href="{{ route('dashboard') }}">
                <img class="max-h-[25px] w-full" src="{{ asset('assets/media/app/mini-logo.svg') }}"/>
            </a>
            <div class="flex items-center">
                <button class="fl-btn fl-btn-icon fl-btn-ghost" data-kt-drawer-toggle="#sidebar">
                    <x-tabler-menu />
                </button>
                <button class="fl-btn fl-btn-icon fl-btn-ghost" data-kt-drawer-toggle="#mega_menu_wrapper">
                    <x-tabler-menu-2-filled />
                </button>
            </div>
        </div>
        <!-- End of Mobile Logo -->
        @include('tenant.partials.mega-menu')
        <!-- Topbar -->
        <div class="flex items-center gap-2.5">
            @include('tenant.partials.topbar-locale-dropdown')
            @include('tenant.partials.topbar-user-dropdown')
        </div>
        <!-- End of Topbar -->
    </div>
    <!-- End of Container -->
</header>
<!-- End of Header -->
