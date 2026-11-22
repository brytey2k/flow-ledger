<!-- Apps -->
<div class="relative shrink-0" x-data="appsDrawer" data-apps-url="{{ route('apps.index') }}">
    <button type="button" class="sgh-btn sgh-btn-icon sgh-btn-ghost" @click="toggle" :aria-expanded="open"
        aria-label="{{ __('navigation.apps') }}" title="{{ __('navigation.apps') }}">
        <x-tabler-grid-dots />
    </button>
    <div x-show="open" x-cloak @click.outside="close()" class="sgh-dropdown-panel w-[340px] max-sm:fixed max-sm:inset-x-4 max-sm:top-16 max-sm:w-auto">
        <div class="px-2.5 py-1.5 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
            {{ __('navigation.apps') }}
        </div>
        <p x-show="status === 'loading'" class="px-2.5 py-2 text-sm text-muted-foreground">{{ __('navigation.apps_loading') }}</p>
        <p x-show="status === 'error'" class="px-2.5 py-2 text-sm text-muted-foreground">{{ __('navigation.apps_unavailable') }}</p>
        <p x-show="status === 'ready' && apps.length === 0" class="px-2.5 py-2 text-sm text-muted-foreground">{{ __('navigation.apps_empty') }}</p>
        <ul x-show="status === 'ready' && apps.length > 0" class="flex flex-col">
            <template x-for="app in apps" :key="app.sign_in_url">
                <li class="flex items-center justify-between gap-3 rounded-md px-2.5 py-2 hover:bg-accent">
                    <span class="truncate text-sm font-medium text-foreground" x-text="app.name"></span>
                    <span class="flex shrink-0 items-center gap-1.5">
                        <template x-if="app.homepage_url">
                            <a :href="app.homepage_url" target="_blank" rel="noopener noreferrer" class="sgh-btn sgh-btn-outline sgh-btn-sm">
                                {{ __('navigation.apps_homepage') }}
                            </a>
                        </template>
                        <a :href="app.sign_in_url" target="_blank" rel="noopener noreferrer" class="sgh-btn sgh-btn-primary sgh-btn-sm">
                            {{ __('navigation.apps_sign_in') }}
                        </a>
                    </span>
                </li>
            </template>
        </ul>
    </div>
</div>
<!-- End of Apps -->
