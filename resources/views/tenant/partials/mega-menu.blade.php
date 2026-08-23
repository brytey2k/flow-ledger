<!-- Mega Menu -->
<div class="flex items-stretch" id="megaMenuContainer">
    <div x-show="$store.megaMenu.open" x-cloak class="fixed inset-0 z-10 bg-foreground/40 lg:hidden" @click="$store.megaMenu.hide()"></div>
    <div
        class="fixed bottom-0 top-0 z-20 hidden w-full max-w-[250px] flex-col gap-5 overflow-auto border-e border-border bg-background p-5 lg:static lg:z-auto lg:flex lg:w-auto lg:max-w-none lg:flex-row lg:items-stretch lg:gap-7.5 lg:border-0 lg:bg-transparent lg:p-0"
        :class="{ 'flex': $store.megaMenu.open }"
        id="mega_menu_wrapper"
    >
        <div class="relative flex items-stretch" x-data="dropdown">
            <button type="button" class="flex items-center gap-1 text-sm font-medium text-secondary-foreground hover:text-primary" @click="toggle" :aria-expanded="open">
                <span class="text-nowrap">
                    Help
                </span>
                <svg class="size-3 shrink-0 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>
            <div x-show="open" x-cloak @click.outside="close()" class="sgh-dropdown-panel w-full max-w-[220px] py-2.5">
                <a class="sgh-dropdown-item" href="{{ route('documentation') }}" tabindex="0">
                    <span class="sgh-nav-icon">
                        <x-tabler-device-tablet-question />
                    </span>
                    <span>
                        Documentation
                    </span>
                </a>
            </div>
        </div>
    </div>
</div>
<!-- End of Mega Menu -->
